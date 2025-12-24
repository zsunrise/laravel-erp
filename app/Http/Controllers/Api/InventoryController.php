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
        $query = Inventory::with(['product', 'warehouse', 'location']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->has('low_stock')) {
            $query->whereRaw('inventories.quantity <= (SELECT min_stock FROM products WHERE products.id = inventories.product_id)');
        }

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
        $inventory = Inventory::with(['product', 'warehouse', 'location'])->findOrFail($id);
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
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        try {
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

            return response()->json($result, 201);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
            'reference_no' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        try {
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

            return response()->json($result, 201);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'from_location_id' => 'nullable|exists:warehouse_locations,id',
            'to_location_id' => 'nullable|exists:warehouse_locations,id',
            'quantity' => 'required|integer|min:1',
            'reference_no' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        try {
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

            return response()->json($result, 201);
        } catch (\Exception $e) {
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
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
            'quantity' => 'required|integer|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_no' => 'nullable|string',
            'remark' => 'nullable|string',
        ]);

        try {
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

            return response()->json($result, 201);
        } catch (\Exception $e) {
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
        $query = InventoryTransaction::with(['product', 'warehouse', 'location', 'user']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

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
        $query = InventoryStocktake::with(['warehouse', 'creator', 'completer']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

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
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'stocktake_date' => 'required|date',
            'remark' => 'nullable|string',
        ]);

        $stocktake = InventoryStocktake::create([
            'stocktake_no' => 'ST' . date('YmdHis') . rand(1000, 9999),
            'warehouse_id' => $validated['warehouse_id'],
            'stocktake_date' => $validated['stocktake_date'],
            'status' => 'draft',
            'created_by' => auth()->id(),
            'remark' => $validated['remark'] ?? null,
        ]);

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
        $stocktake = InventoryStocktake::with(['warehouse', 'creator', 'completer', 'items.product', 'items.location'])
            ->findOrFail($id);
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
        $stocktake = InventoryStocktake::findOrFail($id);

        if ($stocktake->status != 'draft' && $stocktake->status != 'counting') {
            return response()->json(['message' => '盘点单状态不允许添加明细'], 400);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_id' => 'nullable|exists:warehouse_locations,id',
            'actual_quantity' => 'required|integer|min:0',
            'remark' => 'nullable|string',
        ]);

        $inventory = Inventory::where('product_id', $validated['product_id'])
            ->where('warehouse_id', $stocktake->warehouse_id)
            ->where('location_id', $validated['location_id'] ?? null)
            ->first();

        $bookQuantity = $inventory ? $inventory->quantity : 0;
        $unitCost = $inventory ? $inventory->average_cost : 0;

        $item = $stocktake->items()->create([
            'product_id' => $validated['product_id'],
            'location_id' => $validated['location_id'] ?? null,
            'book_quantity' => $bookQuantity,
            'actual_quantity' => $validated['actual_quantity'],
            'unit_cost' => $unitCost,
            'remark' => $validated['remark'] ?? null,
        ]);

        if ($stocktake->status == 'draft') {
            $stocktake->update(['status' => 'counting']);
        }

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
        $stocktake = InventoryStocktake::with('items')->findOrFail($id);

        if ($stocktake->status != 'counting') {
            return response()->json(['message' => '盘点单状态不允许完成'], 400);
        }

        try {
            \DB::transaction(function () use ($stocktake) {
                foreach ($stocktake->items as $item) {
                    if ($item->difference_quantity != 0) {
                        $this->inventoryService->adjust(
                            $item->product_id,
                            $stocktake->warehouse_id,
                            $item->actual_quantity,
                            $item->unit_cost,
                            [
                                'location_id' => $item->location_id,
                                'reference_no' => $stocktake->stocktake_no,
                                'remark' => '盘点调整：' . $item->remark,
                            ]
                        );
                    }
                }

                $stocktake->update([
                    'status' => 'completed',
                    'completed_by' => auth()->id(),
                    'completed_at' => now(),
                ]);
            });

            return response()->json($stocktake->load(['warehouse', 'creator', 'completer', 'items.product']));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

