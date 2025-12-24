<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * 获取用户列表
     *
     * @param Request $request 请求对象，支持 search（搜索关键词）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的用户列表，包含角色信息
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新用户
     *
     * @param Request $request 请求对象，包含用户信息和角色ID数组
     * @return \Illuminate\Http\JsonResponse 返回创建的用户信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return response()->json($user->load('roles'), 201);
    }

    /**
     * 获取指定用户详情
     *
     * @param int $id 用户ID
     * @return \Illuminate\Http\JsonResponse 返回用户详细信息，包含角色信息
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return ApiResponse::success($user, '获取成功');
    }

    /**
     * 更新用户信息
     *
     * @param Request $request 请求对象，包含要更新的用户字段
     * @param int $id 用户ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的用户信息
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return response()->json($user->load('roles'));
    }

    /**
     * 删除用户
     *
     * @param int $id 用户ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
