<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\InventoryStocktake;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * 获取库存列表
     *
     * @param Request $request 请求对象，支持 product_id（产品ID）、warehouse_id（仓库ID）、location_id（库位ID）和 low_stock（低库存）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的库存列表，包含产品、仓库和库位信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载产品、仓库和库位信息
        $query = Inventory::with(['product', 'warehouse', 'location']);

        // 按产品ID筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按库位ID筛选
        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // 筛选低库存产品（库存数量 <= 产品最低库存）
        if ($request->has('low_stock')) {
            $query->whereRaw('inventories.quantity <= (SELECT min_stock FROM products WHERE products.id = inventories.product_id)');
        }

        // 返回分页结果
        return response()->json($query->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取指定库存详情
     *
     * @param int $id 库存ID
     * @return \Illuminate\Http\JsonResponse 返回库存详细信息，包含产品、仓库和库位信息
     */
    public function show($id)
    {
        // 根据ID查询库存记录，预加载关联信息，找不到则抛出404
        $inventory = Inventory::with(['product', 'warehouse', 'location'])->findOrFail($id);
        // 返回标准化成功响应
        return ApiResponse::success($inventory, '获取成功');
    }

    /**
     * 入库操作
     *
     * @param Request $request 请求对象，包含产品ID、仓库ID、数量、单位成本等入库信息
     * @return \Illuminate\Http\JsonResponse 返回入库结果，状态码 201，失败时返回错误消息
     */
    public function stockIn(Request $request)
    {
        // 验证入库参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',       // 产品ID（必填）
            'warehouse_id' => 'required|exists:warehouses,id',   // 仓库ID（必填）
            'location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
            'quantity' => 'required|integer|min:1',              // 入库数量（必填）
            'unit_cost' => 'nullable|numeric|min:0',             // 单位成本
            'reference_type' => 'nullable|string',               // 关联单据类型
            'reference_id' => 'nullable|integer',                // 关联单据ID
            'reference_no' => 'nullable|string',                 // 关联单据号
            'remark' => 'nullable|string',                       // 备注
        ]);

        try {
            // 调用服务层执行入库操作，更新库存数量并记录交易
            $result = $this->inventoryService->stockIn(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                $validated['unit_cost'] ?? 0,
                [
                    'location_id' => $validated['location_id'] ?? null,
                    'reference_type' => $validated['reference_type'] ?? null,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ]
            );

            // 入库成功返回 201 状态码
            return response()->json($result, 201);
        } catch (\Exception $e) {
            // 入库失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 出库操作
     *
     * @param Request $request 请求对象，包含产品ID、仓库ID、数量、单位成本等出库信息
     * @return \Illuminate\Http\JsonResponse 返回出库结果，状态码 201，失败时返回错误消息
     */
    public function stockOut(Request $request)
    {
        // 验证出库参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',       // 产品ID（必填）
            'warehouse_id' => 'required|exists:warehouses,id',   // 仓库ID（必填）
            'location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
            'quantity' => 'required|integer|min:1',              // 出库数量（必填）
            'unit_cost' => 'nullable|numeric|min:0',             // 单位成本
            'reference_type' => 'nullable|string',               // 关联单据类型
            'reference_id' => 'nullable|integer',                // 关联单据ID
            'reference_no' => 'nullable|string',                 // 关联单据号
            'remark' => 'nullable|string',                       // 备注
        ]);

        try {
            // 调用服务层执行出库操作，扣减库存并记录交易
            $result = $this->inventoryService->stockOut(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                $validated['unit_cost'] ?? 0,
                [
                    'location_id' => $validated['location_id'] ?? null,
                    'reference_type' => $validated['reference_type'] ?? null,
                    'reference_id' => $validated['reference_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ]
            );

            // 出库成功返回 201 状态码
            return response()->json($result, 201);
        } catch (\Exception $e) {
            // 出库失败（如库存不足）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 库存调拨
     *
     * @param Request $request 请求对象，包含产品ID、源仓库、目标仓库、数量等调拨信息
     * @return \Illuminate\Http\JsonResponse 返回调拨结果，状态码 201，失败时返回错误消息
     */
    public function transfer(Request $request)
    {
        // 验证调拨参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',              // 产品ID（必填）
            'from_warehouse_id' => 'required|exists:warehouses,id',     // 源仓库ID（必填）
            'to_warehouse_id' => 'required|exists:warehouses,id',       // 目标仓库ID（必填）
            'from_location_id' => 'nullable|exists:warehouse_locations,id', // 源库位ID
            'to_location_id' => 'nullable|exists:warehouse_locations,id',   // 目标库位ID
            'quantity' => 'required|integer|min:1',                     // 调拨数量（必填）
            'reference_no' => 'nullable|string',                        // 关联单据号
            'remark' => 'nullable|string',                              // 备注
        ]);

        try {
            // 调用服务层执行调拨：源仓库出库 + 目标仓库入库
            $result = $this->inventoryService->transfer(
                $validated['product_id'],
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['quantity'],
                [
                    'from_location_id' => $validated['from_location_id'] ?? null,
                    'to_location_id' => $validated['to_location_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ]
            );

            // 调拨成功返回 201 状态码
            return response()->json($result, 201);
        } catch (\Exception $e) {
            // 调拨失败（如源仓库库存不足）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 库存调整
     *
     * @param Request $request 请求对象，包含产品ID、仓库ID、调整后数量、单位成本等调整信息
     * @return \Illuminate\Http\JsonResponse 返回调整结果，状态码 201，失败时返回错误消息
     */
    public function adjust(Request $request)
    {
        // 验证库存调整参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',       // 产品ID（必填）
            'warehouse_id' => 'required|exists:warehouses,id',   // 仓库ID（必填）
            'location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
            'quantity' => 'required|integer|min:0',              // 调整后数量（必填）
            'unit_cost' => 'nullable|numeric|min:0',             // 单位成本
            'reference_no' => 'nullable|string',                 // 关联单据号
            'remark' => 'nullable|string',                       // 备注
        ]);

        try {
            // 调用服务层执行库存调整，设置绝对数量并记录差异
            $result = $this->inventoryService->adjust(
                $validated['product_id'],
                $validated['warehouse_id'],
                $validated['quantity'],
                $validated['unit_cost'] ?? 0,
                [
                    'location_id' => $validated['location_id'] ?? null,
                    'reference_no' => $validated['reference_no'] ?? null,
                    'remark' => $validated['remark'] ?? null,
                ]
            );

            // 调整成功返回 201 状态码
            return response()->json($result, 201);
        } catch (\Exception $e) {
            // 调整失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取库存交易记录列表
     *
     * @param Request $request 请求对象，支持 product_id（产品ID）、warehouse_id（仓库ID）、type（交易类型）和 start_date/end_date（日期范围）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的库存交易记录列表，包含产品、仓库、库位和操作用户信息
     */
    public function transactions(Request $request)
    {
        // 构建查询，预加载产品、仓库、库位和操作用户信息
        $query = InventoryTransaction::with(['product', 'warehouse', 'location', 'user']);

        // 按产品ID筛选
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按交易类型筛选（in/out/transfer/adjust）
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // 按开始日期筛选
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        // 按结束日期筛选
        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // 按交易日期倒序排列，返回分页结果
        return response()->json($query->orderBy('transaction_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 获取盘点单列表
     *
     * @param Request $request 请求对象，支持 warehouse_id（仓库ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的盘点单列表，包含仓库、创建人和完成人信息
     */
    public function stocktakes(Request $request)
    {
        // 构建查询，预加载仓库、创建人和完成人信息
        $query = InventoryStocktake::with(['warehouse', 'creator', 'completer']);

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按盘点单状态筛选（draft/counting/completed）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按盘点日期倒序排列，返回分页结果
        return response()->json($query->orderBy('stocktake_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建盘点单
     *
     * @param Request $request 请求对象，包含仓库ID、盘点日期等盘点单信息
     * @return \Illuminate\Http\JsonResponse 返回创建的盘点单信息，状态码 201
     */
    public function createStocktake(Request $request)
    {
        // 验证盘点单参数
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id', // 仓库ID（必填）
            'stocktake_date' => 'required|date',               // 盘点日期（必填）
            'remark' => 'nullable|string',                     // 备注
        ]);

        // 创建盘点单，自动生成盘点单号，初始状态为 draft
        $stocktake = InventoryStocktake::create([
            'stocktake_no' => 'ST' . date('YmdHis') . rand(1000, 9999), // 生成唯一盘点单号
            'warehouse_id' => $validated['warehouse_id'],
            'stocktake_date' => $validated['stocktake_date'],
            'status' => 'draft',                               // 初始状态：草稿
            'created_by' => auth()->id(),                      // 记录创建人
            'remark' => $validated['remark'] ?? null,
        ]);

        // 返回新建盘点单信息（包含仓库和创建人）
        return response()->json($stocktake->load(['warehouse', 'creator']), 201);
    }

    /**
     * 获取指定盘点单详情
     *
     * @param int $id 盘点单ID
     * @return \Illuminate\Http\JsonResponse 返回盘点单详细信息，包含仓库、创建人、完成人和明细项信息
     */
    public function showStocktake($id)
    {
        // 根据ID查询盘点单，预加载关联信息（仓库、创建人、完成人、明细项）
        $stocktake = InventoryStocktake::with(['warehouse', 'creator', 'completer', 'items.product', 'items.location'])
            ->findOrFail($id); // 找不到则抛出 404 异常
        // 返回标准化成功响应
        return ApiResponse::success($stocktake, '获取成功');
    }

    /**
     * 添加盘点单明细项
     *
     * @param Request $request 请求对象，包含产品ID、库位ID、实际数量等明细信息
     * @param int $id 盘点单ID
     * @return \Illuminate\Http\JsonResponse 返回创建的明细项信息，状态码 201，状态不允许时返回错误消息
     */
    public function addStocktakeItem(Request $request, $id)
    {
        // 根据ID查询盘点单
        $stocktake = InventoryStocktake::findOrFail($id);

        // 检查盘点单状态，只有 draft 或 counting 状态可以添加明细
        if ($stocktake->status != 'draft' && $stocktake->status != 'counting') {
            return response()->json(['message' => '盘点单状态不允许添加明细'], 400);
        }

        // 验证明细项参数
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',       // 产品ID（必填）
            'location_id' => 'nullable|exists:warehouse_locations,id', // 库位ID
            'actual_quantity' => 'required|integer|min:0',      // 实际盘点数量（必填）
            'remark' => 'nullable|string',                       // 备注
        ]);

        // 查询当前库存记录，获取账面数量和单位成本
        $inventory = Inventory::where('product_id', $validated['product_id'])
            ->where('warehouse_id', $stocktake->warehouse_id)
            ->where('location_id', $validated['location_id'] ?? null)
            ->first();

        // 这个账面数量将用于与实际数量对比
        $bookQuantity = $inventory ? $inventory->quantity : 0;
        $unitCost = $inventory ? $inventory->average_cost : 0;

        // 创建盘点明细项
        $item = $stocktake->items()->create([
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'] ?? null,
            'book_quantity' => $bookQuantity,                    // 账面数量
            'actual_quantity' => $validated['actual_quantity'],  // 实盘数量
            'unit_cost' => $unitCost,
            'remark' => $validated['remark'] ?? null,
        ]);

        // 如果盘点单状态为 draft，更新为 counting
        if ($stocktake->status == 'draft') {
            $stocktake->update(['status' => 'counting']);
        }

        // 返回新创建的明细项信息
        return response()->json($item->load(['product', 'location']), 201);
    }

    /**
     * 完成盘点单
     *
     * @param Request $request 请求对象
     * @param int $id 盘点单ID
     * @return \Illuminate\Http\JsonResponse 返回完成后的盘点单信息，失败时返回错误消息
     */
    public function completeStocktake(Request $request, $id)
    {
        // 根据ID查询盘点单，预加载明细项
        $stocktake = InventoryStocktake::with('items')->findOrFail($id);

        // 检查盘点单状态，只有 counting 状态可以完成
        if ($stocktake->status != 'counting') {
            return response()->json(['message' => '盘点单状态不允许完成'], 400);
        }

        try {
            // 使用数据库事务确保数据一致性
            \DB::transaction(function () use ($stocktake) {
                // 遍历所有盘点明细项
                foreach ($stocktake->items as $item) {
                    // 如果存在差异，执行库存调整
                    if ($item->difference_quantity != 0) {
                        $this->inventoryService->adjust(
                            $item->product_id,
                            $stocktake->warehouse_id,
                            $item->actual_quantity,      // 调整为实盘数量
                            $item->unit_cost,
                            [
                                'location_id' => $item->location_id,
                                'reference_no' => $stocktake->stocktake_no,
                                'remark' => '盘点调整：' . $item->remark,
                            ]
                        );
                    }
                }

                // 更新盘点单状态为 completed，记录完成人和完成时间
                $stocktake->update([
                    'status' => 'completed',
                    'completed_by' => auth()->id(),
                    'completed_at' => now(),
                ]);
            });

            // 返回完成后的盘点单信息
            return response()->json($stocktake->load(['warehouse', 'creator', 'completer', 'items.product']));
        } catch (\Exception $e) {
            // 完成失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

