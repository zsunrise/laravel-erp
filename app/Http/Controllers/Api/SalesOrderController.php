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

    /**
     * 获取销售订单列表
     *
     * @param Request $request 请求对象，支持 customer_id（客户ID）、warehouse_id（仓库ID）、status（状态）、start_date/end_date（日期范围）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的销售订单列表，包含客户、仓库、货币和创建人信息
     */
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

    /**
     * 创建销售订单
     *
     * @param Request $request 请求对象，包含订单信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的订单信息，状态码 201，失败时返回错误消息
     */
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

    /**
     * 获取指定销售订单详情
     *
     * @param int $id 销售订单ID
     * @return \Illuminate\Http\JsonResponse 返回订单详细信息，包含客户、仓库、货币、创建人、审批人和明细项信息
     */
    public function show($id)
    {
        $order = SalesOrder::with(['customer', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($order, '获取成功');
    }

    /**
     * 更新销售订单
     *
     * @param Request $request 请求对象，包含要更新的订单字段和明细项
     * @param int $id 销售订单ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的订单信息，失败时返回错误消息
     */
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

    /**
     * 删除销售订单
     *
     * @param int $id 销售订单ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的订单
     */
    public function destroy($id)
    {
        $order = SalesOrder::findOrFail($id);

        if ($order->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的订单'], 400);
        }

        $order->delete();

        return response()->json(['message' => '订单删除成功']);
    }

    /**
     * 审批销售订单
     *
     * @param int $id 销售订单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的订单信息，失败时返回错误消息
     */
    public function approve($id)
    {
        try {
            $order = $this->salesService->approveOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 销售订单发货
     *
     * @param int $id 销售订单ID
     * @param Request $request 请求对象，包含发货明细项数组
     * @return \Illuminate\Http\JsonResponse 返回发货后的订单信息，失败时返回错误消息
     */
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

    /**
     * 取消销售订单
     *
     * @param int $id 销售订单ID
     * @return \Illuminate\Http\JsonResponse 返回取消后的订单信息，失败时返回错误消息
     */
    public function cancel($id)
    {
        try {
            $order = $this->salesService->cancelOrder($id);
            return response()->json($order);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取销售退货单列表
     *
     * @param Request $request 请求对象，支持 customer_id（客户ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的销售退货单列表，包含客户、仓库、货币和创建人信息
     */
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

    /**
     * 创建销售退货单
     *
     * @param Request $request 请求对象，包含退货单信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的退货单信息，状态码 201，失败时返回错误消息
     */
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

    /**
     * 获取指定销售退货单详情
     *
     * @param int $id 销售退货单ID
     * @return \Illuminate\Http\JsonResponse 返回退货单详细信息，包含客户、仓库、货币、创建人、审批人和明细项信息
     */
    public function showReturn($id)
    {
        $return = SalesReturn::with(['customer', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id);
        return ApiResponse::success($return, '获取成功');
    }

    /**
     * 审批销售退货单
     *
     * @param int $id 销售退货单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的退货单信息，失败时返回错误消息
     */
    public function approveReturn($id)
    {
        try {
            $return = $this->salesService->approveReturn($id);
            return response()->json($return);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取销售结算单列表
     *
     * @param Request $request 请求对象，支持 customer_id（客户ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的销售结算单列表，包含客户、货币和创建人信息
     */
    public function settlements(Request $request)
    {
        // 构建查询，预加载关联的客户、货币和创建人信息
        $query = SalesSettlement::with(['customer', 'currency', 'creator']);

        // 按客户ID筛选
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // 按状态筛选（pending-待审核、approved-已审核、paid-已收款）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按结算日期倒序排列，返回分页结果
        return response()->json($query->orderBy('settlement_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建销售结算单
     *
     * @param Request $request 请求对象，包含结算单信息和关联订单/退货单明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的结算单信息，状态码 201，失败时返回错误消息
     */
    public function createSettlement(Request $request)
    {
        // 验证请求参数
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',           // 客户ID（必填）
            'settlement_date' => 'required|date',                      // 结算日期（必填）
            'currency_id' => 'nullable|exists:currencies,id',          // 币种ID（可选）
            'remark' => 'nullable|string',                             // 备注（可选）
            'items' => 'required|array|min:1',                         // 结算明细（至少一条）
            'items.*.reference_type' => 'required|in:sales_order,sales_return', // 关联类型：销售订单或销售退货
            'items.*.reference_id' => 'required|integer',              // 关联单据ID
            'items.*.remark' => 'nullable|string',                     // 明细备注（可选）
        ]);

        try {
            // 调用服务层创建结算单，自动计算金额并生成结算单号
            $settlement = $this->salesService->createSettlement($validated);
            // 创建成功返回 201 状态码
            return response()->json($settlement, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定销售结算单详情
     *
     * @param int $id 销售结算单ID
     * @return \Illuminate\Http\JsonResponse 返回结算单详细信息，包含客户、货币、创建人、审批人和明细项信息
     */
    public function showSettlement($id)
    {
        // 查询结算单，预加载关联信息（客户、货币、创建人、审批人、明细项）
        $settlement = SalesSettlement::with(['customer', 'currency', 'creator', 'approver', 'items'])
            ->findOrFail($id); // 找不到则抛出 404 异常
        // 返回标准化成功响应
        return ApiResponse::success($settlement, '获取成功');
    }

    /**
     * 审批销售结算单
     *
     * @param int $id 销售结算单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的结算单信息，失败时返回错误消息
     */
    public function approveSettlement($id)
    {
        try {
            // 调用服务层审批结算单，将状态从 pending 更新为 approved
            $settlement = $this->salesService->approveSettlement($id);
            // 审批成功返回结算单信息
            return response()->json($settlement);
        } catch (\Exception $e) {
            // 审批失败（如状态不正确）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 收款（销售结算单）
     *
     * @param int $id 销售结算单ID
     * @param Request $request 请求对象，包含 received_amount（收款金额）
     * @return \Illuminate\Http\JsonResponse 返回收款后的结算单信息，失败时返回错误消息
     */
    public function receivePayment($id, Request $request)
    {
        // 验证收款金额参数
        $validated = $request->validate([
            'received_amount' => 'required|numeric|min:0', // 收款金额（必填，非负数）
        ]);

        try {
            // 调用服务层处理收款，累加已收金额，全额收款后状态更新为 paid
            $settlement = $this->salesService->receivePayment($id, $validated['received_amount']);
            // 收款成功返回结算单信息
            return response()->json($settlement);
        } catch (\Exception $e) {
            // 收款失败（如金额超出余额）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

