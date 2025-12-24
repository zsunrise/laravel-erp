<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ProcessRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProcessRouteController extends Controller
{
    /**
     * 获取工艺路线列表
     *
     * @param Request $request 请求对象，支持 product_id（产品ID）、is_active（是否激活）、is_default（是否默认）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工艺路线列表，包含产品和创建人信息
     */
    public function index(Request $request)
    {
        $query = ProcessRoute::with(['product', 'creator']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->leftJoin('products', 'process_routes.product_id', '=', 'products.id')
                  ->where(function($q) use ($search) {
                      $q->where('process_routes.version', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('products.sku', 'like', "%{$search}%");
                  })
                  ->select('process_routes.*')
                  ->distinct();
        }

        return response()->json($query->orderBy('effective_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工艺路线
     *
     * @param Request $request 请求对象，包含工艺路线信息和步骤数组
     * @return \Illuminate\Http\JsonResponse 返回创建的工艺路线信息，状态码 201
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'version' => 'required|string|max:50',
            'effective_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'steps' => 'required|array|min:1',
            'steps.*.step_name' => 'required|string|max:255',
            'steps.*.step_code' => 'nullable|string|max:100',
            'steps.*.sequence' => 'required|integer|min:1',
            'steps.*.work_center' => 'nullable|string|max:100',
            'steps.*.standard_time' => 'nullable|numeric|min:0',
            'steps.*.setup_time' => 'nullable|numeric|min:0',
            'steps.*.queue_time' => 'nullable|numeric|min:0',
            'steps.*.move_time' => 'nullable|numeric|min:0',
            'steps.*.description' => 'nullable|string',
            'steps.*.remark' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            if ($validated['is_default'] ?? false) {
                ProcessRoute::where('product_id', $validated['product_id'])
                    ->update(['is_default' => false]);
            }

            $processRoute = ProcessRoute::create([
                'product_id' => $validated['product_id'],
                'version' => $validated['version'],
                'effective_date' => $validated['effective_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['steps'] as $stepData) {
                $processRoute->steps()->create([
                    'step_name' => $stepData['step_name'],
                    'step_code' => $stepData['step_code'] ?? null,
                    'sequence' => $stepData['sequence'],
                    'work_center' => $stepData['work_center'] ?? null,
                    'standard_time' => $stepData['standard_time'] ?? 0,
                    'setup_time' => $stepData['setup_time'] ?? 0,
                    'queue_time' => $stepData['queue_time'] ?? 0,
                    'move_time' => $stepData['move_time'] ?? 0,
                    'description' => $stepData['description'] ?? null,
                    'remark' => $stepData['remark'] ?? null,
                ]);
            }

            return response()->json($processRoute->load(['product', 'steps', 'creator']), 201);
        });
    }

    /**
     * 获取指定工艺路线详情
     *
     * @param int $id 工艺路线ID
     * @return \Illuminate\Http\JsonResponse 返回工艺路线详细信息，包含产品、步骤和创建人信息
     */
    public function show($id)
    {
        $processRoute = ProcessRoute::with(['product', 'steps', 'creator'])
            ->findOrFail($id);
        return ApiResponse::success($processRoute, '获取成功');
    }

    /**
     * 更新工艺路线信息
     *
     * @param Request $request 请求对象，包含要更新的工艺路线字段和步骤
     * @param int $id 工艺路线ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的工艺路线信息
     */
    public function update(Request $request, $id)
    {
        $processRoute = ProcessRoute::findOrFail($id);

        $validated = $request->validate([
            'version' => 'sometimes|required|string|max:50',
            'effective_date' => 'sometimes|required|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'is_default' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'steps' => 'sometimes|required|array|min:1',
            'steps.*.step_name' => 'required|string|max:255',
            'steps.*.step_code' => 'nullable|string|max:100',
            'steps.*.sequence' => 'required|integer|min:1',
            'steps.*.work_center' => 'nullable|string|max:100',
            'steps.*.standard_time' => 'nullable|numeric|min:0',
            'steps.*.setup_time' => 'nullable|numeric|min:0',
            'steps.*.queue_time' => 'nullable|numeric|min:0',
            'steps.*.move_time' => 'nullable|numeric|min:0',
            'steps.*.description' => 'nullable|string',
            'steps.*.remark' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($processRoute, $validated) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                ProcessRoute::where('product_id', $processRoute->product_id)
                    ->where('id', '!=', $processRoute->id)
                    ->update(['is_default' => false]);
            }

            $processRoute->update([
                'version' => $validated['version'] ?? $processRoute->version,
                'effective_date' => $validated['effective_date'] ?? $processRoute->effective_date,
                'expiry_date' => $validated['expiry_date'] ?? $processRoute->expiry_date,
                'is_default' => $validated['is_default'] ?? $processRoute->is_default,
                'is_active' => $validated['is_active'] ?? $processRoute->is_active,
                'description' => $validated['description'] ?? $processRoute->description,
            ]);

            if (isset($validated['steps'])) {
                $processRoute->steps()->delete();

                foreach ($validated['steps'] as $stepData) {
                    $processRoute->steps()->create([
                        'step_name' => $stepData['step_name'],
                        'step_code' => $stepData['step_code'] ?? null,
                        'sequence' => $stepData['sequence'],
                        'work_center' => $stepData['work_center'] ?? null,
                        'standard_time' => $stepData['standard_time'] ?? 0,
                        'setup_time' => $stepData['setup_time'] ?? 0,
                        'queue_time' => $stepData['queue_time'] ?? 0,
                        'move_time' => $stepData['move_time'] ?? 0,
                        'description' => $stepData['description'] ?? null,
                        'remark' => $stepData['remark'] ?? null,
                    ]);
                }
            }

            return response()->json($processRoute->load(['product', 'steps', 'creator']));
        });
    }

    /**
     * 删除工艺路线
     *
     * @param int $id 工艺路线ID
     * @return \Illuminate\Http\JsonResponse 返回删除成功消息
     */
    public function destroy($id)
    {
        $processRoute = ProcessRoute::findOrFail($id);
        $processRoute->delete();

        return response()->json(['message' => '工艺路线删除成功']);
    }

    /**
     * 设置工艺路线为默认版本
     *
     * @param int $id 工艺路线ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的工艺路线信息
     */
    public function setDefault($id)
    {
        $processRoute = ProcessRoute::findOrFail($id);

        DB::transaction(function () use ($processRoute) {
            ProcessRoute::where('product_id', $processRoute->product_id)
                ->update(['is_default' => false]);

            $processRoute->update(['is_default' => true]);
        });

        return response()->json($processRoute->load(['product', 'steps']));
    }

    /**
     * 复制工艺路线
     *
     * @param int $id 工艺路线ID
     * @return \Illuminate\Http\JsonResponse 返回复制的工艺路线信息，状态码 201
     */
    public function copy($id)
    {
        $sourceRoute = ProcessRoute::with('steps')->findOrFail($id);

        return DB::transaction(function () use ($sourceRoute) {
            $newRoute = $sourceRoute->replicate();
            $newRoute->version = $sourceRoute->version . '_copy';
            $newRoute->is_default = false;
            $newRoute->created_by = auth()->id();
            $newRoute->save();

            foreach ($sourceRoute->steps as $step) {
                $newStep = $step->replicate();
                $newStep->process_route_id = $newRoute->id;
                $newStep->save();
            }

            return response()->json($newRoute->load(['product', 'steps', 'creator']), 201);
        });
    }
}
