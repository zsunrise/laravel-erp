<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemConfig::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderBy('group')->orderBy('key')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:system_configs,key',
            'value' => 'nullable|string',
            'type' => 'sometimes|in:string,number,boolean,json',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $config = SystemConfig::create($validated);

        return response()->json($config, 201);
    }

    public function show($id)
    {
        $config = SystemConfig::findOrFail($id);
        return ApiResponse::success($config, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $config = SystemConfig::findOrFail($id);

        $validated = $request->validate([
            'key' => 'sometimes|required|string|max:255|unique:system_configs,key,' . $id,
            'value' => 'nullable|string',
            'type' => 'sometimes|in:string,number,boolean,json',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $config->update($validated);

        return response()->json($config);
    }

    public function destroy($id)
    {
        $config = SystemConfig::findOrFail($id);
        $config->delete();

        return response()->json(['message' => '配置删除成功']);
    }

    public function getByKey($key)
    {
        $config = SystemConfig::where('key', $key)->first();
        
        if (!$config) {
            return response()->json(['message' => '配置不存在'], 404);
        }

        return response()->json($config);
    }
}
