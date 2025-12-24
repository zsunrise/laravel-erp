<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomReportController extends Controller
{
    /**
     * 获取自定义报表定义列表
     *
     * @queryParam category string 报表分类（sales/purchase/inventory/financial/custom） Example: sales
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 category（分类）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的自定义报表定义列表，包含创建人信息，按创建时间降序排列
     */
    public function index(Request $request)
    {
        // 构建查询，预加载创建人信息
        $query = ReportDefinition::with(['creator']);

        // 按报表分类筛选
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // 按是否启用筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按创建时间倒序排列，返回分页结果
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建自定义报表定义
     *
     * @bodyParam name string required 报表名称 Example: 销售日报
     * @bodyParam code string required 报表编码（唯一） Example: SALES_DAILY
     * @bodyParam category string required 报表分类（sales/purchase/inventory/financial/custom） Example: sales
     * @bodyParam description string 报表描述 Example: 每日销售统计报表
     * @bodyParam query_config array required 查询配置 Example: 查询配置对象
     * @bodyParam display_config array required 显示配置 Example: 显示配置对象
     * @bodyParam is_active boolean 是否启用 Example: true
     * @param Request $request 请求对象，包含报表定义信息（名称、编码、分类、查询配置、显示配置等）
     * @return \Illuminate\Http\JsonResponse 返回创建的报表定义信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证报表定义参数
        $validated = $request->validate([
            'name' => 'required|string|max:255',                                // 报表名称
            'code' => 'required|string|unique:report_definitions,code',         // 报表编码（唯一）
            'category' => 'required|in:sales,purchase,inventory,financial,custom', // 报表分类
            'description' => 'nullable|string',                                 // 报表描述
            'query_config' => 'required|array',                                 // 查询配置（JSON）
            'display_config' => 'required|array',                               // 显示配置（JSON）
            'is_active' => 'sometimes|boolean',                                 // 是否启用
        ]);

        // 创建报表定义
        $report = ReportDefinition::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'query_config' => $validated['query_config'],
            'display_config' => $validated['display_config'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);

        // 返回创建成功响应
        return response()->json($report->load('creator'), 201);
    }

    /**
     * 获取指定报表定义详情
     *
     * @param int $id 报表定义ID
     * @return \Illuminate\Http\JsonResponse 返回报表定义详细信息，包含创建人和计划任务信息
     */
    public function show($id)
    {
        // 根据ID查询报表定义，预加载创建人和计划任务信息
        $report = ReportDefinition::with(['creator', 'schedules'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($report, '获取成功');
    }

    /**
     * 更新自定义报表定义
     *
     * @bodyParam name string 报表名称 Example: 销售日报
     * @bodyParam code string 报表编码（唯一，排除当前报表） Example: SALES_DAILY
     * @bodyParam category string 报表分类（sales/purchase/inventory/financial/custom） Example: sales
     * @bodyParam description string 报表描述 Example: 每日销售统计报表
     * @bodyParam query_config array 查询配置 Example: 查询配置对象
     * @bodyParam display_config array 显示配置 Example: 显示配置对象
     * @bodyParam is_active boolean 是否启用 Example: true
     * @param Request $request 请求对象，包含要更新的报表定义字段
     * @param int $id 报表定义ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的报表定义信息
     */
    public function update(Request $request, $id)
    {
        // 根据ID查询报表定义
        $report = ReportDefinition::findOrFail($id);

        // 验证更新参数
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:report_definitions,code,' . $id,
            'category' => 'sometimes|required|in:sales,purchase,inventory,financial,custom',
            'description' => 'nullable|string',
            'query_config' => 'sometimes|required|array',
            'display_config' => 'sometimes|required|array',
            'is_active' => 'sometimes|boolean',
        ]);

        // 更新报表定义
        $report->update($validated);

        // 返回更新后的报表信息
        return response()->json($report->load('creator'));
    }

    /**
     * 删除自定义报表定义
     *
     * @param int $id 报表定义ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        // 根据ID查询报表定义
        $report = ReportDefinition::findOrFail($id);
        // 删除报表定义
        $report->delete();

        // 返回删除成功消息
        return response()->json(['message' => '报表删除成功']);
    }

    /**
     * 执行自定义报表
     *
     * @param int $id 报表定义ID
     * @param Request $request 请求对象，包含报表查询参数
     * @return \Illuminate\Http\JsonResponse 返回报表数据和报表定义信息，报表未启用或配置错误时返回错误消息
     */
    public function execute($id, Request $request)
    {
        // 根据ID查询报表定义
        $report = ReportDefinition::findOrFail($id);

        // 检查报表是否启用
        if (!$report->is_active) {
            return response()->json(['message' => '报表未启用'], 400);
        }

        // 解析查询配置
        $queryConfig = $report->query_config;
        $table = $queryConfig['table'] ?? null;            // 表名
        $fields = $queryConfig['fields'] ?? ['*'];         // 查询字段
        $conditions = $queryConfig['conditions'] ?? [];    // 查询条件
        $groupBy = $queryConfig['group_by'] ?? null;       // 分组字段
        $orderBy = $queryConfig['order_by'] ?? null;       // 排序字段

        // 检查表名是否配置
        if (!$table) {
            return response()->json(['message' => '报表配置错误'], 400);
        }

        // 构建数据库查询
        $query = DB::table($table);

        // 设置查询字段
        if ($fields !== ['*']) {
            $query->select($fields);
        }

        // 处理查询条件
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if ($field && $value !== null) {
                // 如果请求中包含该字段，使用请求值
                if ($request->has($field)) {
                    $value = $request->get($field);
                }

                // 根据操作符添加条件
                switch ($operator) {
                    case '=':
                        $query->where($field, $value);
                        break;
                    case '>':
                        $query->where($field, '>', $value);
                        break;
                    case '<':
                        $query->where($field, '<', $value);
                        break;
                    case '>=':
                        $query->where($field, '>=', $value);
                        break;
                    case '<=':
                        $query->where($field, '<=', $value);
                        break;
                    case 'like':
                        $query->where($field, 'like', "%{$value}%");
                        break;
                    case 'between':
                        if (is_array($value) && count($value) == 2) {
                            $query->whereBetween($field, $value);
                        }
                        break;
                }
            }
        }

        // 处理分组
        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        // 处理排序
        if ($orderBy) {
            foreach ($orderBy as $order) {
                $query->orderBy($order['field'], $order['direction'] ?? 'asc');
            }
        }

        // 执行查询，获取报表数据
        $data = $query->get();

        // 返回报表定义和数据
        return response()->json([
            'report' => $report,
            'data' => $data,
        ]);
    }
}
