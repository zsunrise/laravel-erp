<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
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

    public function show($id)
    {
        $permission = Permission::with(['roles'])->findOrFail($id);
        return ApiResponse::success($permission, '获取成功');
    }

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
