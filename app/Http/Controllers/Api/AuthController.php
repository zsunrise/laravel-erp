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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Account is disabled.'],
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load(['roles.permissions']);
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();

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
        $request->user()->currentAccessToken()->delete();

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
        $user = $request->user();
        $user->load(['roles.permissions']);
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();

        return response()->json(array_merge($user->toArray(), ['permissions' => $permissions]));
    }
}
