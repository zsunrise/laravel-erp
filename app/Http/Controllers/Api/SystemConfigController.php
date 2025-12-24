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
        // 构建查询
        $query = SystemConfig::query();

        // 按配置组筛选
        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        // 关键词搜索：支持搜索键名和描述
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 按组和键名排序，返回分页结果
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
        // 验证配置参数
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:system_configs,key', // 配置键名（唯一）
            'value' => 'nullable|string',                                 // 配置值
            'type' => 'sometimes|in:string,number,boolean,json',          // 值类型
            'group' => 'nullable|string|max:255',                         // 配置组
            'description' => 'nullable|string',                           // 描述
        ]);

        // 创建配置记录
        $config = SystemConfig::create($validated);

        // 返回新建配置信息
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
        // 根据ID查询配置，找不到则抛出404
        $config = SystemConfig::findOrFail($id);
        // 返回标准化成功响应
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
        // 根据ID查询配置
        $config = SystemConfig::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'key' => 'sometimes|required|string|max:255|unique:system_configs,key,' . $id,
            'value' => 'nullable|string',
            'type' => 'sometimes|in:string,number,boolean,json',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // 更新配置信息
        $config->update($validated);

        // 返回更新后的配置信息
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
        // 根据ID查询配置
        $config = SystemConfig::findOrFail($id);
        // 删除配置记录
        $config->delete();

        // 返回删除成功消息
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
        // 根据键名查询配置
        $config = SystemConfig::where('key', $key)->first();
        
        // 不存在则返回404错误
        if (!$config) {
            return response()->json(['message' => '配置不存在'], 404);
        }

        // 返回配置信息
        return response()->json($config);
    }
}
