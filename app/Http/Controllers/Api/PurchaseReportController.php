<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseReportController extends Controller
{
    /**
     * 采购汇总报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按日期分组的采购数据统计和汇总信息（总金额、订单数、平均金额、供应商数）
     */
    public function summary(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按日期分组的数据
        $data = PurchaseOrder::selectRaw("DATE_FORMAT(order_date, '%Y-%m-%d') as date")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->selectRaw('COUNT(DISTINCT supplier_id) as supplier_count')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 统计信息
        $query = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $totalAmount = $query->sum('total_amount');
        $orderCount = $query->count();
        $supplierCount = $query->distinct('supplier_id')->count('supplier_id');

        $stats = [
            'total_amount' => $totalAmount ?? 0,
            'order_count' => $orderCount,
            'avg_amount' => $orderCount > 0 ? round($totalAmount / $orderCount, 2) : 0,
            'supplier_count' => $supplierCount,
        ];

        return ApiResponse::success([
            'data' => $data,
            'stats' => $stats,
        ], '获取成功');
    }

    /**
     * 按供应商统计采购报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按供应商分组的采购统计（订单数、总金额、平均金额），按总金额降序排列
     */
    public function bySupplier(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = PurchaseOrder::select('suppliers.id', 'suppliers.name', 'suppliers.code')
            ->selectRaw('COUNT(purchase_orders.id) as order_count')
            ->selectRaw('SUM(purchase_orders.total_amount) as total_amount')
            ->selectRaw('AVG(purchase_orders.total_amount) as avg_amount')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.code')
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json($report);
    }

    /**
     * 按产品统计采购报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按产品分组的采购统计（总数量、总金额、平均价格），按总金额降序排列
     */
    public function byProduct(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = PurchaseOrderItem::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(purchase_order_items.quantity) as total_quantity')
            ->selectRaw('SUM(purchase_order_items.total_amount) as total_amount')
            ->selectRaw('AVG(purchase_order_items.unit_price) as avg_price')
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id')
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json($report);
    }

    /**
     * 采购趋势报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）、end_date（结束日期）和 group_by（分组方式：day/week/month）
     * @return \Illuminate\Http\JsonResponse 返回按时间段分组的采购趋势数据（订单数和总金额）
     */
    public function trend(Request $request)
    {
        $startDate = $request->start_date ?? now()->subMonths(6)->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();
        $groupBy = $request->group_by ?? 'day';

        $format = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $report = PurchaseOrder::selectRaw("DATE_FORMAT(order_date, '{$format}') as period")
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json($report);
    }
}
