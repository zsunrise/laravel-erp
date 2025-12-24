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
     * @param Request $request 请求对象，支持 category（分类）和 is_active（是否激活）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的自定义报表定义列表，包含创建人信息，按创建时间降序排列
     */
    public function index(Request $request)
    {
        $query = ReportDefinition::with(['creator']);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建自定义报表定义
     *
     * @param Request $request 请求对象，包含报表定义信息（名称、编码、分类、查询配置、显示配置等）
     * @return \Illuminate\Http\JsonResponse 返回创建的报表定义信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:report_definitions,code',
            'category' => 'required|in:sales,purchase,inventory,financial,custom',
            'description' => 'nullable|string',
            'query_config' => 'required|array',
            'display_config' => 'required|array',
            'is_active' => 'sometimes|boolean',
        ]);

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
        $report = ReportDefinition::with(['creator', 'schedules'])->findOrFail($id);
        return ApiResponse::success($report, '获取成功');
    }

    /**
     * 更新自定义报表定义
     *
     * @param Request $request 请求对象，包含要更新的报表定义字段
     * @param int $id 报表定义ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的报表定义信息
     */
    public function update(Request $request, $id)
    {
        $report = ReportDefinition::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:report_definitions,code,' . $id,
            'category' => 'sometimes|required|in:sales,purchase,inventory,financial,custom',
            'description' => 'nullable|string',
            'query_config' => 'sometimes|required|array',
            'display_config' => 'sometimes|required|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $report->update($validated);

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
        $report = ReportDefinition::findOrFail($id);
        $report->delete();

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
        $report = ReportDefinition::findOrFail($id);

        if (!$report->is_active) {
            return response()->json(['message' => '报表未启用'], 400);
        }

        $queryConfig = $report->query_config;
        $table = $queryConfig['table'] ?? null;
        $fields = $queryConfig['fields'] ?? ['*'];
        $conditions = $queryConfig['conditions'] ?? [];
        $groupBy = $queryConfig['group_by'] ?? null;
        $orderBy = $queryConfig['order_by'] ?? null;

        if (!$table) {
            return response()->json(['message' => '报表配置错误'], 400);
        }

        $query = DB::table($table);

        if ($fields !== ['*']) {
            $query->select($fields);
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if ($field && $value !== null) {
                if ($request->has($field)) {
                    $value = $request->get($field);
                }

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

        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        if ($orderBy) {
            foreach ($orderBy as $order) {
                $query->orderBy($order['field'], $order['direction'] ?? 'asc');
            }
        }

        $data = $query->get();

        return response()->json([
            'report' => $report,
            'data' => $data,
        ]);
    }
}
