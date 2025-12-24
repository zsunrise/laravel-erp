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
        $query = Role::with(['permissions']);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

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
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
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
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:roles,slug,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update($validated);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

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
        $role = Role::findOrFail($id);

        if ($role->users()->count() > 0) {
            return response()->json(['message' => '该角色下还有用户，无法删除'], 400);
        }

        $role->delete();

        return response()->json(['message' => '角色删除成功']);
    }
}
