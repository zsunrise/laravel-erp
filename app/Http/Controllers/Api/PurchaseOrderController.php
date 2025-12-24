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

    /**
     * 获取采购订单列表
     *
     * @queryParam supplier_id integer 供应商ID Example: 1
     * @queryParam warehouse_id integer 仓库ID Example: 1
     * @queryParam status string 订单状态（draft/approved/receiving/completed/cancelled） Example: approved
     * @queryParam start_date date 开始日期 Example: 2024-01-01
     * @queryParam end_date date 结束日期 Example: 2024-12-31
     * @queryParam search string 搜索关键词（按订单号/供应商名称模糊匹配） Example: PO001
     * @queryParam per_page integer 每页数量 Example: 15
     * @param Request $request 请求对象，支持 supplier_id（供应商ID）、warehouse_id（仓库ID）、status（状态）、start_date/end_date（日期范围）和 search（搜索关键词）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的采购订单列表，包含供应商、仓库、货币和创建人信息
     */
    public function index(Request $request)
    {
        // 构建查询，预加载供应商、仓库、货币和创建人信息
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'currency', 'creator']);

        // 按供应商ID筛选
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 按仓库ID筛选
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // 按订单状态筛选（draft/approved/receiving/completed/cancelled）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按开始日期筛选
        if ($request->has('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }

        // 按结束日期筛选
        if ($request->has('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // 关键词搜索：按订单号或供应商名称模糊匹配
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

        // 按订单日期倒序排列，返回分页结果
        return response()->json($query->orderBy('order_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建采购订单
     *
     * @bodyParam supplier_id integer required 供应商ID Example: 1
     * @bodyParam warehouse_id integer required 仓库ID Example: 1
     * @bodyParam order_date date required 订单日期 Example: 2024-01-15
     * @bodyParam expected_date date 预计到货日期 Example: 2024-01-20
     * @bodyParam currency_id integer 币种ID Example: 1
     * @bodyParam discount_amount number 折扣金额 Example: 100
     * @bodyParam remark string 备注 Example: 紧急订单
     * @bodyParam items array required 订单明细（至少一条） Example: 订单明细数组
     * @bodyParam items.*.product_id integer required 产品ID Example: 1
     * @bodyParam items.*.quantity integer required 数量（最小1） Example: 10
     * @bodyParam items.*.unit_price number required 单价 Example: 100
     * @bodyParam items.*.tax_rate number 税率（0-100） Example: 13
     * @bodyParam items.*.discount_rate number 折扣率（0-100） Example: 5
     * @bodyParam items.*.remark string 明细备注 Example: 优质产品
     * @param Request $request 请求对象，包含订单信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的订单信息，状态码 201，失败时返回错误消息
     */
    public function store(Request $request)
    {
        // 验证采购订单参数
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',     // 供应商ID（必填）
            'warehouse_id' => 'required|exists:warehouses,id',   // 仓库ID（必填）
            'order_date' => 'required|date',                     // 订单日期（必填）
            'expected_date' => 'nullable|date',                  // 预计到货日期
            'currency_id' => 'nullable|exists:currencies,id',    // 币种ID
            'discount_amount' => 'nullable|numeric|min:0',       // 折扣金额
            'remark' => 'nullable|string',                       // 备注
            'items' => 'required|array|min:1',                   // 订单明细（至少一条）
            'items.*.product_id' => 'required|exists:products,id', // 产品ID
            'items.*.quantity' => 'required|integer|min:1',      // 数量
            'items.*.unit_price' => 'required|numeric|min:0',    // 单价
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100', // 税率
            'items.*.discount_rate' => 'nullable|numeric|min:0|max:100', // 折扣率
            'items.*.remark' => 'nullable|string',               // 明细备注
        ]);

        try {
            // 调用服务层创建采购订单，自动生成订单号并计算金额
            $order = $this->purchaseService->createOrder($validated);
            // 创建成功返回 201 状态码
            return response()->json($order, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定采购订单详情
     *
     * @param int $id 采购订单ID
     * @return \Illuminate\Http\JsonResponse 返回订单详细信息，包含供应商、仓库、货币、创建人、审批人和明细项信息
     */
    public function show($id)
    {
        // 根据ID查询采购订单，预加载关联信息（供应商、仓库、货币、创建人、审批人、明细项）
        $order = PurchaseOrder::with(['supplier', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id); // 找不到则抛出 404 异常
        // 返回标准化成功响应
        return ApiResponse::success($order, '获取成功');
    }

    /**
     * 更新采购订单
     *
     * @param Request $request 请求对象，包含要更新的订单字段和明细项
     * @param int $id 采购订单ID
     * @return \Illuminate\Http\JsonResponse 返回更新后的订单信息，失败时返回错误消息
     */
    public function update(Request $request, $id)
    {
        // 验证更新参数
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
            // 调用服务层更新采购订单（只能更新草稿状态的订单）
            $order = $this->purchaseService->updateOrder($id, $validated);
            // 更新成功返回订单信息
            return response()->json($order);
        } catch (\Exception $e) {
            // 更新失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 删除采购订单
     *
     * @param int $id 采购订单ID
     * @return \Illuminate\Http\JsonResponse 返回删除结果，只能删除草稿状态的订单
     */
    public function destroy($id)
    {
        // 根据ID查询采购订单
        $order = PurchaseOrder::findOrFail($id);

        // 检查订单状态，只能删除草稿状态的订单
        if ($order->status != 'draft') {
            return response()->json(['message' => '只能删除草稿状态的订单'], 400);
        }

        // 删除订单记录
        $order->delete();

        // 返回删除成功消息
        return response()->json(['message' => '订单删除成功']);
    }

    /**
     * 审批采购订单
     *
     * @param int $id 采购订单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的订单信息，失败时返回错误消息
     */
    public function approve($id)
    {
        try {
            // 调用服务层审批订单，将状态从 draft 更新为 approved
            $order = $this->purchaseService->approveOrder($id);
            // 审批成功返回订单信息
            return response()->json($order);
        } catch (\Exception $e) {
            // 审批失败（如状态不正确）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 采购订单收货
     *
     * @param int $id 采购订单ID
     * @param Request $request 请求对象，包含收货明细项数组
     * @return \Illuminate\Http\JsonResponse 返回收货后的订单信息，失败时返回错误消息
     */
    public function receive($id, Request $request)
    {
        // 验证收货明细参数
        $validated = $request->validate([
            'items' => 'required|array|min:1',                           // 收货明细（至少一条）
            'items.*.item_id' => 'required|exists:purchase_order_items,id', // 订单明细ID
            'items.*.quantity' => 'required|integer|min:1',              // 收货数量
        ]);

        try {
            // 调用服务层处理收货，更新已收数量，全部收货后状态更新为 completed
            $order = $this->purchaseService->receiveGoods($id, $validated['items']);
            // 收货成功返回订单信息
            return response()->json($order);
        } catch (\Exception $e) {
            // 收货失败（如数量超出）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 取消采购订单
     *
     * @param int $id 采购订单ID
     * @return \Illuminate\Http\JsonResponse 返回取消后的订单信息，失败时返回错误消息
     */
    public function cancel($id)
    {
        try {
            // 调用服务层取消订单，将状态更新为 cancelled
            $order = $this->purchaseService->cancelOrder($id);
            // 取消成功返回订单信息
            return response()->json($order);
        } catch (\Exception $e) {
            // 取消失败（如已收货）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取采购退货单列表
     *
     * @param Request $request 请求对象，支持 supplier_id（供应商ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的采购退货单列表，包含供应商、仓库、货币和创建人信息
     */
    public function returns(Request $request)
    {
        // 构建查询，预加载供应商、仓库、货币和创建人信息
        $query = PurchaseReturn::with(['supplier', 'warehouse', 'currency', 'creator']);

        // 按供应商ID筛选
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 按退货单状态筛选
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按退货日期倒序排列，返回分页结果
        return response()->json($query->orderBy('return_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建采购退货单
     *
     * @param Request $request 请求对象，包含退货单信息和明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的退货单信息，状态码 201，失败时返回错误消息
     */
    public function createReturn(Request $request)
    {
        // 验证采购退货单参数
        $validated = $request->validate([
            'purchase_order_id' => 'nullable|exists:purchase_orders,id', // 关联采购订单ID（可选）
            'supplier_id' => 'required|exists:suppliers,id',             // 供应商ID（必填）
            'warehouse_id' => 'required|exists:warehouses,id',           // 仓库ID（必填）
            'return_date' => 'required|date',                            // 退货日期（必填）
            'currency_id' => 'nullable|exists:currencies,id',            // 币种ID
            'remark' => 'nullable|string',                               // 备注
            'items' => 'required|array|min:1',                           // 退货明细（至少一条）
            'items.*.product_id' => 'required|exists:products,id',       // 产品ID
            'items.*.quantity' => 'required|integer|min:1',              // 退货数量
            'items.*.unit_price' => 'required|numeric|min:0',            // 单价
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',      // 税率
            'items.*.remark' => 'nullable|string',                       // 明细备注
        ]);

        try {
            // 调用服务层创建退货单，自动生成退货单号
            $return = $this->purchaseService->createReturn($validated);
            // 创建成功返回 201 状态码
            return response()->json($return, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定采购退货单详情
     *
     * @param int $id 采购退货单ID
     * @return \Illuminate\Http\JsonResponse 返回退货单详细信息，包含供应商、仓库、货币、创建人、审批人和明细项信息
     */
    public function showReturn($id)
    {
        // 根据ID查询退货单，预加载关联信息
        $return = PurchaseReturn::with(['supplier', 'warehouse', 'currency', 'creator', 'approver', 'items.product'])
            ->findOrFail($id); // 找不到则抛出 404 异常
        // 返回标准化成功响应
        return ApiResponse::success($return, '获取成功');
    }

    /**
     * 审批采购退货单
     *
     * @param int $id 采购退货单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的退货单信息，失败时返回错误消息
     */
    public function approveReturn($id)
    {
        try {
            // 调用服务层审批退货单，将状态从 pending 更新为 approved
            $return = $this->purchaseService->approveReturn($id);
            // 审批成功返回退货单信息
            return response()->json($return);
        } catch (\Exception $e) {
            // 审批失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取采购结算单列表
     *
     * @param Request $request 请求对象，支持 supplier_id（供应商ID）和 status（状态）筛选
     * @return \Illuminate\Http\JsonResponse 返回分页的采购结算单列表，包含供应商、货币和创建人信息
     */
    public function settlements(Request $request)
    {
        // 构建查询，预加载供应商、货币和创建人信息
        $query = PurchaseSettlement::with(['supplier', 'currency', 'creator']);

        // 按供应商ID筛选
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // 按结算单状态筛选（pending/approved/paid）
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // 按结算日期倒序排列，返回分页结果
        return response()->json($query->orderBy('settlement_date', 'desc')->paginate($request->get('per_page', 15)));
    }

    /**
     * 创建采购结算单
     *
     * @param Request $request 请求对象，包含结算单信息和关联订单/退货单明细项数组
     * @return \Illuminate\Http\JsonResponse 返回创建的结算单信息，状态码 201，失败时返回错误消息
     */
    public function createSettlement(Request $request)
    {
        // 验证采购结算单参数
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',               // 供应商ID（必填）
            'settlement_date' => 'required|date',                          // 结算日期（必填）
            'currency_id' => 'nullable|exists:currencies,id',              // 币种ID
            'remark' => 'nullable|string',                                 // 备注
            'items' => 'required|array|min:1',                             // 结算明细（至少一条）
            'items.*.reference_type' => 'required|in:purchase_order,purchase_return', // 关联类型
            'items.*.reference_id' => 'required|integer',                  // 关联单据ID
            'items.*.remark' => 'nullable|string',                         // 明细备注
        ]);

        try {
            // 调用服务层创建结算单，自动计算金额
            $settlement = $this->purchaseService->createSettlement($validated);
            // 创建成功返回 201 状态码
            return response()->json($settlement, 201);
        } catch (\Exception $e) {
            // 创建失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 获取指定采购结算单详情
     *
     * @param int $id 采购结算单ID
     * @return \Illuminate\Http\JsonResponse 返回结算单详细信息，包含供应商、货币、创建人、审批人和明细项信息
     */
    public function showSettlement($id)
    {
        // 根据ID查询结算单，预加载关联信息
        $settlement = PurchaseSettlement::with(['supplier', 'currency', 'creator', 'approver', 'items'])
            ->findOrFail($id); // 找不到则抛出 404 异常
        // 返回标准化成功响应
        return ApiResponse::success($settlement, '获取成功');
    }

    /**
     * 审批采购结算单
     *
     * @param int $id 采购结算单ID
     * @return \Illuminate\Http\JsonResponse 返回审批后的结算单信息，失败时返回错误消息
     */
    public function approveSettlement($id)
    {
        try {
            // 调用服务层审批结算单，将状态从 pending 更新为 approved
            $settlement = $this->purchaseService->approveSettlement($id);
            // 审批成功返回结算单信息
            return response()->json($settlement);
        } catch (\Exception $e) {
            // 审批失败返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * 支付采购结算单
     *
     * @param int $id 采购结算单ID
     * @param Request $request 请求对象，包含 paid_amount（支付金额）
     * @return \Illuminate\Http\JsonResponse 返回支付后的结算单信息，失败时返回错误消息
     */
    public function paySettlement($id, Request $request)
    {
        // 验证支付金额参数
        $validated = $request->validate([
            'paid_amount' => 'required|numeric|min:0', // 支付金额（必填，非负数）
        ]);

        try {
            // 调用服务层处理支付，累加已付金额，全额支付后状态更新为 paid
            $settlement = $this->purchaseService->paySettlement($id, $validated['paid_amount']);
            // 支付成功返回结算单信息
            return response()->json($settlement);
        } catch (\Exception $e) {
            // 支付失败（如金额超出余额）返回错误消息
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

