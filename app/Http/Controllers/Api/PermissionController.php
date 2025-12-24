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
        $query = Permission::with(['roles']);

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($validated);

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
        $permission = Permission::with(['roles'])->findOrFail($id);
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
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:permissions,slug,' . $id,
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission->update($validated);

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
        $permission = Permission::findOrFail($id);

        if ($permission->roles()->count() > 0) {
            return ApiResponse::error('该权限已被角色使用，无法删除', 400);
        }

        $permission->delete();

        return ApiResponse::success(null, '删除成功');
    }
}
