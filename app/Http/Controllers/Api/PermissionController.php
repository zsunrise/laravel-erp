<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

        return response()->json($query->paginate($request->get('per_page', 15)));
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

        return response()->json($permission, 201);
    }

    public function show($id)
    {
        $permission = Permission::with(['roles'])->findOrFail($id);
        return response()->json($permission);
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

        return response()->json($permission);
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);

        if ($permission->roles()->count() > 0) {
            return response()->json(['message' => '该权限已被角色使用，无法删除'], 400);
        }

        $permission->delete();

        return response()->json(['message' => '权限删除成功']);
    }
}
