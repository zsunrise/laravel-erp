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
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按日期分组的采购数据统计和汇总信息（总金额、订单数、平均金额、供应商数）
     */
    public function summary(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按日期分组统计采购数据
        $data = PurchaseOrder::selectRaw("DATE_FORMAT(order_date, '%Y-%m-%d') as date")
            ->selectRaw('COUNT(*) as order_count')       // 订单数
            ->selectRaw('SUM(total_amount) as total_amount') // 总金额
            ->selectRaw('COUNT(DISTINCT supplier_id) as supplier_count') // 供应商数
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')  // 排除已取消订单
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 汇总统计信息
        $query = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $totalAmount = $query->sum('total_amount');
        $orderCount = $query->count();
        $supplierCount = $query->distinct('supplier_id')->count('supplier_id');

        // 构建统计数据
        $stats = [
            'total_amount' => $totalAmount ?? 0,    // 总采购额
            'order_count' => $orderCount,           // 订单数
            'avg_amount' => $orderCount > 0 ? round($totalAmount / $orderCount, 2) : 0, // 平均订单金额
            'supplier_count' => $supplierCount,     // 供应商数
        ];

        // 返回报表数据和统计信息
        return ApiResponse::success([
            'data' => $data,
            'stats' => $stats,
        ], '获取成功');
    }

    /**
     * 按供应商统计采购报表
     *
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按供应商分组的采购统计（订单数、总金额、平均金额），按总金额降序排列
     */
    public function bySupplier(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按供应商分组统计采购数据
        $report = PurchaseOrder::select('suppliers.id', 'suppliers.name', 'suppliers.code')
            ->selectRaw('COUNT(purchase_orders.id) as order_count')       // 订单数
            ->selectRaw('SUM(purchase_orders.total_amount) as total_amount') // 总金额
            ->selectRaw('AVG(purchase_orders.total_amount) as avg_amount')   // 平均金额
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id') // 关联供应商表
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.code')
            ->orderBy('total_amount', 'desc') // 按总金额降序排列
            ->get();

        return response()->json($report);
    }

    /**
     * 按产品统计采购报表
     *
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按产品分组的采购统计（总数量、总金额、平均价格），按总金额降序排列
     */
    public function byProduct(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按产品分组统计采购数据
        $report = PurchaseOrderItem::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(purchase_order_items.quantity) as total_quantity')   // 总数量
            ->selectRaw('SUM(purchase_order_items.total_amount) as total_amount') // 总金额
            ->selectRaw('AVG(purchase_order_items.unit_price) as avg_price')      // 平均价格
            ->join('products', 'purchase_order_items.product_id', '=', 'products.id') // 关联产品表
            ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id') // 关联订单表
            ->whereBetween('purchase_orders.order_date', [$startDate, $endDate])
            ->where('purchase_orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc') // 按总金额降序排列
            ->get();

        return response()->json($report);
    }

    /**
     * 采购趋势报表
     *
     * @queryParam start_date date 开始日期（默认最近6个月） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-06-30
     * @queryParam group_by string 分组方式（day/week/month，默认day） Example: month
     * @param Request $request 请求对象，支持 start_date（开始日期）、end_date（结束日期）和 group_by（分组方式：day/week/month）
     * @return \Illuminate\Http\JsonResponse 返回按时间段分组的采购趋势数据（订单数和总金额）
     */
    public function trend(Request $request)
    {
        // 获取日期范围，默认为最近6个月
        $startDate = $request->start_date ?? now()->subMonths(6)->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();
        $groupBy = $request->group_by ?? 'day'; // 分组方式：day/week/month

        // 根据分组方式确定日期格式
        $format = match($groupBy) {
            'day' => '%Y-%m-%d',   // 按天
            'week' => '%Y-%u',     // 按周
            'month' => '%Y-%m',    // 按月
            default => '%Y-%m-%d',
        };

        // 按时间段分组统计采购趋势
        $report = PurchaseOrder::selectRaw("DATE_FORMAT(order_date, '{$format}') as period")
            ->selectRaw('COUNT(*) as order_count')       // 订单数
            ->selectRaw('SUM(total_amount) as total_amount') // 总金额
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json($report);
    }
}
