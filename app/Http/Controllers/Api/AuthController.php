<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 用户登录接口
     *
     * @param Request $request 请求对象，包含 email 和 password
     * @return \Illuminate\Http\JsonResponse 返回用户信息和认证令牌
     * @throws ValidationException 当凭证错误或账户被禁用时抛出异常
     * @unauthenticated
     */
    public function login(Request $request)
    {
        // 验证登录参数：邮箱和密码必填
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 根据邮箱查询用户
        $user = User::where('email', $request->email)->first();

        // 验证用户是否存在及密码是否正确
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // 检查账户是否被禁用
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is disabled.'],
            ]);
        }

        // 更新用户最后登录时间和IP
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // 创建 Sanctum 认证令牌
        $token = $user->createToken('auth-token')->plainTextToken;

        // 加载用户角色和权限信息
        $user->load(['roles.permissions']);
        // 提取并去重所有权限
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();

        // 返回用户信息和令牌
        return response()->json([
            'user' => array_merge($user->toArray(), ['permissions' => $permissions]),
            'token' => $token,
        ]);
    }

    /**
     * 用户登出接口
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回登出成功消息
     */
    public function logout(Request $request)
    {
        // 删除当前用户的访问令牌，使其失效
        $request->user()->currentAccessToken()->delete();

        // 返回登出成功消息
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * 获取当前登录用户信息
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回当前用户信息和权限列表
     */
    public function me(Request $request)
    {
        // 获取当前认证用户
        $user = $request->user();
        // 加载用户的角色和权限关联
        $user->load(['roles.permissions']);
        // 从所有角色中提取权限并去重
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();

        // 返回用户信息（包含权限列表）
        return response()->json(array_merge($user->toArray(), ['permissions' => $permissions]));
    }
}
