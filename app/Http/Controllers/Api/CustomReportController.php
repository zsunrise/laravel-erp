<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomReportController extends Controller
{
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

    public function show($id)
    {
        $report = ReportDefinition::with(['creator', 'schedules'])->findOrFail($id);
        return ApiResponse::success($report, '获取成功');
    }

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

    public function destroy($id)
    {
        $report = ReportDefinition::findOrFail($id);
        $report->delete();

        return response()->json(['message' => '报表删除成功']);
    }

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
