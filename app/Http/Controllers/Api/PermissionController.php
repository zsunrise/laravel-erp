<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * 获取权限列表
     *
     * @param Request $request 请求对象，支持 group（权限组）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的权限列表，包含角色信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载角色信息
        $query = Permission::with(['roles']);

        // 按权限组筛选
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        // 关键词搜索：按权限名称或标识模糊匹配
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // 返回分页结果
        $permissions = $query->paginate($request->get('per_page', 15));
        return ApiResponse::paginated($permissions, '获取成功');
    }

    /**
     * 创建新权限
     *
     * @param Request $request 请求对象，包含权限信息（名称、标识、分组等）
     * @return \Illuminate\Http\JsonResponse 返回创建的权限信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证权限信息参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                    // 权限名称（必填）
            'slug' => 'required|string|max:255|unique:permissions,slug', // 权限标识（唯一）
            'group' => 'nullable|string|max:255',                   // 权限组
            'description' => 'nullable|string',                     // 描述
        ]);

        // 创建权限记录
        $permission = Permission::create($validated);

        // 返回创建成功响应
        return ApiResponse::success($permission, '创建成功', 201);
    }

    /**
     * 获取指定权限详情
     *
     * @param int $id 权限ID
     * @return \Illuminate\Http\JsonResponse 返回权限详细信息，包含角色信息
     */
    public function show($id)
    {
        // 根据ID查询权限，预加载角色信息，找不到则抛出404
        $permission = Permission::with(['roles'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($permission, '获取成功');
    }

    /**
     * 更新权限信息
     *
     * @param Request $request 请求对象，包含要更新的权限字段
     * @param int $id 权限ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的权限信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询权限
        $permission = Permission::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:permissions,slug,' . $id,
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // 更新权限信息
        $permission->update($validated);

        // 返回更新成功响应
        return ApiResponse::success($permission, '更新成功');
    }

    /**
     * 删除权限
     *
     * @param int $id 权限ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，如果权限已被角色使用则返回错误消息
     */
    public function destroy($id)
    {
        // 根据ID查询权限
        $permission = Permission::findOrFail($id);

        // 检查是否有关联角色，有则不允许删除
        if ($permission->roles()->count() > 0) {
            return ApiResponse::error('该权限已被角色使用，无法删除', 400);
        }

        // 删除权限记录
        $permission->delete();

        // 返回删除成功响应
        return ApiResponse::success(null, '删除成功');
    }
}
