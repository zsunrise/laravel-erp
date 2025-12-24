<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * 获取角色列表
     *
     * @param Request $request 请求对象，支持 is_active（是否激活）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的角色列表，包含权限信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载权限信息
        $query = Role::with(['permissions']);

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 关键词搜索：按角色名称或标识模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // 返回分页结果
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建新角色
     *
     * @param Request $request 请求对象，包含角色信息和权限ID数组
     * @return \Illuminate\Http\JsonResponse 返回创建的角色信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证角色信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                      // 角色名称（必填）
            'slug' => 'required|string|max:255|unique:roles,slug',    // 角色标识（唯一）
            'description' => 'nullable|string',                       // 描述
            'is_active' => 'sometimes|boolean',                       // 是否激活
            'permissions' => 'nullable|array',                        // 权限ID数组
            'permissions.*' => 'exists:permissions,id',               // 权限必须存在
        ]);

        // 创建角色记录
        $role = Role::create($validated);

        // 如果提供了权限，同步角色权限关系
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        // 返回新建角色信息（包含权限）
        return response()->json($role->load('permissions'), 201);
    }

    /**
     * 获取指定角色详情
     *
     * @param int $id 角色ID
     * @return \Illuminate\Http\JsonResponse 返回角色详细信息，包含权限和用户信息
     */
    public function show($id)
    {
        // 根据ID查询角色，预加载权限和用户信息，找不到则抛出404
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($role, '获取成功');
    }

    /**
     * 更新角色信息
     *
     * @param Request $request 请求对象，包含要更新的角色字段和权限ID数组
     * @param int $id 角色ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的角色信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询角色
        $role = Role::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // 更新角色信息
        $role->update($validated);

        // 如果提供了权限，同步角色权限关系
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        // 返回更新后的角色信息
        return response()->json($role->load('permissions'));
    }

    /**
     * 删除角色
     *
     * @param int $id 角色ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果角色下有用户则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询角色
        $role = Role::findOrFail($id);

        // 检查是否有关联用户，有则不允许删除
        if ($role->users()->count() > 0) {
            return response()->json(['message' => '该角色下还有用户，无法删除'], 400);
        }

        // 删除角色记录
        $role->delete();

        // 返回删除成功消息
        return response()->json(['message' => '角色删除成功']);
    }
}
