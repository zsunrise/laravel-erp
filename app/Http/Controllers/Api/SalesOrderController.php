<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesSettlement;
use App\Services\SalesService;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    protected $salesService;

    public function __construct(SalesService $salesService)
    {
        $this->salesService = $salesService;
    }

    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'warehouse', 'currency', 'creator']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
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
            $query->leftJoin('customers', 'sales_orders.customer_id', '=', 'customers.id')
                  ->where(function($q) use ($search) {
                      $q->where('sales_orders.order_no', 'like', "%{$search}%")
                        ->orWhere('customers.name', 'like', "%{$search}%");
                  })
                  ->select('sales_orders.*')
                  ->distinct();
        }

        return response()->json($query->orderBy('order_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date',
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
            $order = $this->salesService->createOrder($validated);
            return response()->json($order, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
        $order = SalesOrder::with(['customer', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($order, '获取成功');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'warehouse_id' => 'sometimes|required|exists:warehouses,id',
            'order_date' => 'sometimes|required|date',
            'delivery_date' => 'nullable|date',
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
            $order = $this->salesService->updateOrder($id, $validated);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function destroy($id)
    {
        $order = SalesOrder::findOrFail($id);

        if ($order->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的订单'], 400);
        }

        $order->delete();

        return response()->json(['message' => '订单删除成功']);
    }

    public function approve($id)
    {
        try {
            $order = $this->salesService->approveOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function ship($id, Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:sales_order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->salesService->shipGoods($id, $validated['items']);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel($id)
    {
        try {
            $order = $this->salesService->cancelOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function returns(Request $request)
    {
        $query = SalesReturn::with(['customer', 'warehouse', 'currency', 'creator']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('return_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function createReturn(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'customer_id' => 'required|exists:customers,id',
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
            $return = $this->salesService->createReturn($validated);
            return response()->json($return, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function showReturn($id)
    {
        $return = SalesReturn::with(['customer', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($return, '获取成功');
    }

    public function approveReturn($id)
    {
        try {
            $return = $this->salesService->approveReturn($id);
            return response()->json($return);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function settlements(Request $request)
    {
        $query = SalesSettlement::with(['customer', 'currency', 'creator']);

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('settlement_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function createSettlement(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'settlement_date' => 'required|date',
            'currency_id' => 'nullable|exists:currencies,id',
            'remark' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.reference_type' => 'required|in:sales_order,sales_return',
            'items.*.reference_id' => 'required|integer',
            'items.*.remark' => 'nullable|string',
        ]);

        try {
            $settlement = $this->salesService->createSettlement($validated);
            return response()->json($settlement, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function showSettlement($id)
    {
        $settlement = SalesSettlement::with(['customer', 'currency', 'creator', 'approver', 'items'])
            ->findOrFail($id);
        return ApiResponse::success($settlement, '获取成功');
    }

    public function approveSettlement($id)
    {
        try {
            $settlement = $this->salesService->approveSettlement($id);
            return response()->json($settlement);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function receivePayment($id, Request $request)
    {
        $validated = $request->validate([
            'received_amount' => 'required|numeric|min:0',
        ]);

        try {
            $settlement = $this->salesService->receivePayment($id, $validated['received_amount']);
            return response()->json($settlement);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

