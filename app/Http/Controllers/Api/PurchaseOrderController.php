<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\PurchaseSettlement;
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    protected $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'currency', 'creator']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->leftJoin('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
                  ->where(function($q) use ($search) {
                      $q->where('purchase_orders.order_no', 'like', "%{$search}%")
                        ->orWhere('suppliers.name', 'like', "%{$search}%");
                  })
                  ->select('purchase_orders.*')
                  ->distinct();
        }

        return response()->json($query->orderBy('order_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $order = $this->purchaseService->createOrder($validated);
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['supplier', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($order, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'supplier_id' => 'sometimes|required|exists:suppliers,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'order_date' => 'sometimes|required|date',
            'expected_date' => 'nullable|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'discount_amount' => 'nullable|numeric|min:0',
            'remark' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $order = $this->purchaseService->updateOrder($id, $validated);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的订单'], 400);
        }

        $order->delete();

        return response()->json(['message' => '订单删除成功']);
    }

    public function approve($id)
    {
        try {
            $order = $this->purchaseService->approveOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function receive($id, Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->purchaseService->receiveGoods($id, $validated['items']);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel($id)
    {
        try {
            $order = $this->purchaseService->cancelOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function returns(Request $request)
    {
        $query = PurchaseReturn::with(['supplier', 'warehouse', 'currency', 'creator']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('return_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function createReturn(Request $request)
    {
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $return = $this->purchaseService->createReturn($validated);
            return response()->json($return, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function showReturn($id)
    {
        $return = PurchaseReturn::with(['supplier', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($return, '获取成功');
    }

    public function approveReturn($id)
    {
        try {
            $return = $this->purchaseService->approveReturn($id);
            return response()->json($return);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function settlements(Request $request)
    {
        $query = PurchaseSettlement::with(['supplier', 'currency', 'creator']);

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('settlement_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function createSettlement(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'settlement_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.reference_type' => 'required|in:purchase_order,purchase_return',
            'items.*.reference_id' => 'required|integer',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $settlement = $this->purchaseService->createSettlement($validated);
            return response()->json($settlement, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function showSettlement($id)
    {
        $settlement = PurchaseSettlement::with(['supplier', 'currency', 'creator', 'approver', 'items'])
            ->findOrFail($id);
        return ApiResponse::success($settlement, '获取成功');
    }

    public function approveSettlement($id)
    {
        try {
            $settlement = $this->purchaseService->approveSettlement($id);
            return response()->json($settlement);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function paySettlement($id, Request $request)
    {
        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        try {
            $settlement = $this->purchaseService->paySettlement($id, $validated['paid_amount']);
            return response()->json($settlement);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

