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
     * @queryParam search string 搜索关键词（按用户名/邮箱/电话模糊匹配） Example: 张三
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 search（搜索关键词）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的用户列表，包含角色信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载用户的角色信息
        $query = User::with('roles');

        // 关键词搜索：按用户名、邮箱、电话模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 返回分页结果
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新用户
     *
     * @bodyParam name string required 用户名 Example: 张三
     * @bodyParam email string required 邮箱（唯一） Example: zhangsan@example.com
     * @bodyParam password string required 密码（至少8位） Example: password123
     * @bodyParam phone string 电话 Example: 13800138000
     * @bodyParam roles array 角色ID数组 Example: 1,2
     * @bodyParam roles.* integer 角色ID（必须存在） Example: 1
     * @param Request $request 请求对象，包含用户信息和角色ID数组
     * @return \Illuminate\Http\JsonResponse 返回创建的用户信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证用户信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                      // 用户名（必填）
            'email' => 'required|string|email|max:255|unique:users',  // 邮箱（唯一）
            'password' => 'required|string|min:8',                    // 密码（必填，至少8位）
            'phone' => 'nullable|string|max:20',                      // 电话
            'roles' => 'nullable|array',                              // 角色ID数组
            'roles.*' => 'exists:roles,id',                           // 角色必须存在
        ]);

        // 对密码进行哈希加密
        $validated['password'] = Hash::make($validated['password']);
        // 创建用户记录
        $user = User::create($validated);

        // 如果提供了角色，同步用户角色关系
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        // 返回新建用户信息（包含角色）
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
        // 根据ID查询用户，预加载角色信息，找不到则抛出404
        $user = User::with('roles')->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($user, '获取成功');
    }

    /**
     * 更新用户信息
     *
     * @bodyParam name string 用户名 Example: 张三
     * @bodyParam email string 邮箱（唯一，排除当前用户） Example: zhangsan@example.com
     * @bodyParam password string 密码（至少8位，为空则不更新） Example: newpassword123
     * @bodyParam phone string 电话 Example: 13800138000
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam roles array 角色ID数组 Example: 1,2
     * @bodyParam roles.* integer 角色ID（必须存在） Example: 1
     * @param Request $request 请求对象，包含要更新的用户字段
     * @param int $id 用户ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的用户信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询用户
        $user = User::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // 如果提供了新密码，进行哈希加密
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            // 没有提供密码则不更新密码字段
            unset($validated['password']);
        }

        // 更新用户信息
        $user->update($validated);

        // 如果提供了角色，同步用户角色关系
        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        // 返回更新后的用户信息
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
        // 根据ID查询用户
        $user = User::findOrFail($id);
        // 删除用户记录
        $user->delete();

        // 返回删除成功消息
        return response()->json(['message' => 'User deleted successfully']);
    }
}
