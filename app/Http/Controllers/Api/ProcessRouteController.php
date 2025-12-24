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
     * @queryParam product_id integer 产品ID Example: 1
     * @queryParam is_active boolean 是否激活（1:激活, 0:未激活） Example: 1
     * @queryParam is_default boolean 是否默认版本（1:是, 0:否） Example: 1
     * @queryParam search string 搜索关键词（按版本号/产品名称/SKU模糊匹配） Example: V1.0
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 product_id（产品ID）、is_active（是否激活）、is_default（是否默认）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的工艺路线列表，包含产品和创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载产品和创建人信息
        $query = ProcessRoute::with(['product', 'creator']);

        // 按产品ID筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按激活状态筛选
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // 按默认版本筛选
        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        // 关键词搜索：按版本号、产品名称或SKU模糊匹配
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

        // 按生效日期倒序排列，返回分页结果
        return response()->json($query->orderBy('effective_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建工艺路线
     *
     * @bodyParam product_id integer required 产品ID Example: 1
     * @bodyParam version string required 版本号 Example: V1.0
     * @bodyParam effective_date date required 生效日期 Example: 2024-01-01
     * @bodyParam expiry_date date 失效日期（必须晚于生效日期） Example: 2024-12-31
     * @bodyParam is_default boolean 是否默认版本 Example: true
     * @bodyParam is_active boolean 是否激活 Example: true
     * @bodyParam description string 描述 Example: 初始版本工艺路线
     * @bodyParam steps array required 工序步骤（至少一个） Example: 工序步骤数组
     * @bodyParam steps.*.step_name string required 工序名称 Example: 加工
     * @bodyParam steps.*.step_code string 工序编码 Example: STEP001
     * @bodyParam steps.*.sequence integer required 工序顺序（最小1） Example: 1
     * @bodyParam steps.*.work_center string 工作中心 Example: 加工中心1
     * @bodyParam steps.*.standard_time number 标准工时 Example: 2.5
     * @bodyParam steps.*.setup_time number 准备时间 Example: 0.5
     * @bodyParam steps.*.queue_time number 排队时间 Example: 0.2
     * @bodyParam steps.*.move_time number 转移时间 Example: 0.1
     * @bodyParam steps.*.description string 工序描述 Example: 主要加工工序
     * @bodyParam steps.*.remark string 备注 Example: 关键工序
     * @param Request $request 请求对象，包含工艺路线信息和步骤数组
     * @return \Illuminate\Http\JsonResponse 返回创建的工艺路线信息，状态码 201
     */
    public function store(Request $request)
    {
        // 验证工艺路线参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',        // 产品ID（必填）
            'version' => 'required|string|max:50',                // 版本号（必填）
            'effective_date' => 'required|date',                  // 生效日期（必填）
            'expiry_date' => 'nullable|date|after:effective_date', // 失效日期
            'is_default' => 'sometimes|boolean',                  // 是否默认版本
            'is_active' => 'sometimes|boolean',                   // 是否激活
            'description' => 'nullable|string',                   // 描述
            'steps' => 'required|array|min:1',                    // 工序步骤（至少一个）
            'steps.*.step_name' => 'required|string|max:255',     // 工序名称
            'steps.*.step_code' => 'nullable|string|max:100',     // 工序编码
            'steps.*.sequence' => 'required|integer|min:1',       // 工序顺序
            'steps.*.work_center' => 'nullable|string|max:100',   // 工作中心
            'steps.*.standard_time' => 'nullable|numeric|min:0',  // 标准工时
            'steps.*.setup_time' => 'nullable|numeric|min:0',     // 准备时间
            'steps.*.queue_time' => 'nullable|numeric|min:0',     // 排队时间
            'steps.*.move_time' => 'nullable|numeric|min:0',      // 转移时间
            'steps.*.description' => 'nullable|string',           // 工序描述
            'steps.*.remark' => 'nullable|string',                // 备注
        ]);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($validated) {
            // 如果设置为默认版本，先清除该产品其他工艺路线的默认标记
            if ($validated['is_default'] ?? false) {
                ProcessRoute::where('product_id', $validated['product_id'])
                    ->update(['is_default' => false]);
            }

            // 创建工艺路线主记录
            $processRoute = ProcessRoute::create([
                'product_id' => $validated['product_id'],
                'version' => $validated['version'],
                'effective_date' => $validated['effective_date'],
                'expiry_date' => $validated['expiry_date'] ?? null,
                'is_default' => $validated['is_default'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'description' => $validated['description'] ?? null,
                'created_by' => auth()->id(), // 记录创建人
            ]);

            // 创建工序步骤
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

            // 返回新建工艺路线信息（包含关联）
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
        // 根据ID查询工艺路线，预加载产品、工序步骤和创建人信息
        $processRoute = ProcessRoute::with(['product', 'steps', 'creator'])
            ->findOrFail($id); // 找不到则抛出404异常
        // 返回标准化成功响应
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
        // 根据ID查询工艺路线
        $processRoute = ProcessRoute::findOrFail($id);

        // 验证更新参数
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

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($processRoute, $validated) {
            // 如果设置为默认版本，清除该产品其他工艺路线的默认标记
            if (isset($validated['is_default']) && $validated['is_default']) {
                ProcessRoute::where('product_id', $processRoute->product_id)
                    ->where('id', '!=', $processRoute->id)
                    ->update(['is_default' => false]);
            }

            // 更新工艺路线主记录
            $processRoute->update([
                'version' => $validated['version'] ?? $processRoute->version,
                'effective_date' => $validated['effective_date'] ?? $processRoute->effective_date,
                'expiry_date' => $validated['expiry_date'] ?? $processRoute->expiry_date,
                'is_default' => $validated['is_default'] ?? $processRoute->is_default,
                'is_active' => $validated['is_active'] ?? $processRoute->is_active,
                'description' => $validated['description'] ?? $processRoute->description,
            ]);

            // 如果提供了工序步骤，则重新创建
            if (isset($validated['steps'])) {
                // 删除旧的工序步骤
                $processRoute->steps()->delete();

                // 创建新的工序步骤
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

            // 返回更新后的工艺路线信息
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
        // 根据ID查询工艺路线
        $processRoute = ProcessRoute::findOrFail($id);
        // 删除工艺路线记录（级联删除工序步骤）
        $processRoute->delete();

        // 返回删除成功消息
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
        // 根据ID查询工艺路线
        $processRoute = ProcessRoute::findOrFail($id);

        // 使用事务确保数据一致性
        DB::transaction(function () use ($processRoute) {
            // 先清除该产品所有工艺路线的默认标记
            ProcessRoute::where('product_id', $processRoute->product_id)
                ->update(['is_default' => false]);

            // 将当前工艺路线设置为默认
            $processRoute->update(['is_default' => true]);
        });

        // 返回更新后的工艺路线信息
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
        // 根据ID查询源工艺路线，预加载工序步骤
        $sourceRoute = ProcessRoute::with('steps')->findOrFail($id);

        // 使用事务确保数据一致性
        return DB::transaction(function () use ($sourceRoute) {
            // 复制工艺路线主记录
            $newRoute = $sourceRoute->replicate();
            $newRoute->version = $sourceRoute->version . '_copy'; // 版本号加后缀
            $newRoute->is_default = false;                         // 新副本不是默认版本
            $newRoute->created_by = auth()->id();                 // 记录新的创建人
            $newRoute->save();

            // 复制工序步骤
            foreach ($sourceRoute->steps as $step) {
                $newStep = $step->replicate();
                $newStep->process_route_id = $newRoute->id; // 关联到新工艺路线
                $newStep->save();
            }

            // 返回复制后的工艺路线信息
            return response()->json($newRoute->load(['product', 'steps', 'creator']), 201);
        });
    }
}
