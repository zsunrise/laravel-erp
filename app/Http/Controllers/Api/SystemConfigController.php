<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * 获取系统配置列表
     *
     * @param Request $request 请求对象，支持 group（配置组）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的系统配置列表
     */
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

    /**
     * 创建系统配置
     *
     * @param Request $request 请求对象，包含配置信息（键、值、类型、分组等）
     * @return \Illuminate\Http\JsonResponse 返回创建的配置信息，状态码 201
     */
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

    /**
     * 获取指定配置详情
     *
     * @param int $id 配置ID
     * @return \Illuminate\Http\JsonResponse 返回配置详细信息
     */
    public function show($id)
    {
        $config = SystemConfig::findOrFail($id);
        return ApiResponse::success($config, '获取成功');
    }

    /**
     * 更新系统配置
     *
     * @param Request $request 请求对象，包含要更新的配置字段
     * @param int $id 配置ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的配置信息
     */
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

    /**
     * 删除系统配置
     *
     * @param int $id 配置ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $config = SystemConfig::findOrFail($id);
        $config->delete();

        return response()->json(['message' => '配置删除成功']);
    }

    /**
     * 根据键名获取配置
     *
     * @param string $key 配置键名
     * @return \Illuminate\Http\JsonResponse 返回配置信息，不存在时返回404错误
     */
    public function getByKey($key)
    {
        $config = SystemConfig::where('key', $key)->first();
        
        if (!$config) {
            return response()->json(['message' => '配置不存在'], 404);
        }

        return response()->json($config);
    }
}
