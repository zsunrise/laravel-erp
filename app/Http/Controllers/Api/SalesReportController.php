<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    /**
     * 销售汇总报表
     *
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按日期分组的销售数据统计和汇总信息（总金额、订单数、平均金额、客户数）
     */
    public function summary(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按日期分组统计销售数据
        $data = SalesOrder::selectRaw("DATE_FORMAT(order_date, '%Y-%m-%d') as date")
            ->selectRaw('COUNT(*) as order_count')       // 订单数
            ->selectRaw('SUM(total_amount) as total_amount') // 总金额
            ->selectRaw('COUNT(DISTINCT customer_id) as customer_count') // 客户数
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')  // 排除已取消订单
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 汇总统计信息
        $query = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $totalAmount = $query->sum('total_amount');
        $orderCount = $query->count();
        $customerCount = $query->distinct('customer_id')->count('customer_id');

        // 构建统计数据
        $stats = [
            'total_amount' => $totalAmount ?? 0,    // 总销售额
            'order_count' => $orderCount,           // 订单数
            'avg_amount' => $orderCount > 0 ? round($totalAmount / $orderCount, 2) : 0, // 平均订单金额
            'customer_count' => $customerCount,     // 客户数
        ];

        // 返回报表数据和统计信息
        return ApiResponse::success([
            'data' => $data,
            'stats' => $stats,
        ], '获取成功');
    }

    /**
     * 按客户统计销售报表
     *
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按客户分组的销售统计（订单数、总金额、平均金额），按总金额降序排列
     */
    public function byCustomer(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按客户分组统计销售数据
        $report = SalesOrder::select('customers.id', 'customers.name', 'customers.code')
            ->selectRaw('COUNT(sales_orders.id) as order_count')       // 订单数
            ->selectRaw('SUM(sales_orders.total_amount) as total_amount') // 总金额
            ->selectRaw('AVG(sales_orders.total_amount) as avg_amount')   // 平均金额
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id') // 关联客户表
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->where('sales_orders.status', '!=', 'cancelled')
            ->groupBy('customers.id', 'customers.name', 'customers.code')
            ->orderBy('total_amount', 'desc') // 按总金额降序排列
            ->get();

        return response()->json($report);
    }

    /**
     * 按产品统计销售报表
     *
     * @queryParam start_date date 开始日期（默认本月第一天） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-01-31
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按产品分组的销售统计（总数量、总金额、平均价格），按总金额降序排列
     */
    public function byProduct(Request $request)
    {
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按产品分组统计销售数据
        $report = SalesOrderItem::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(sales_order_items.quantity) as total_quantity')   // 总数量
            ->selectRaw('SUM(sales_order_items.total_amount) as total_amount') // 总金额
            ->selectRaw('AVG(sales_order_items.unit_price) as avg_price')      // 平均价格
            ->join('products', 'sales_order_items.product_id', '=', 'products.id') // 关联产品表
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id') // 关联订单表
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->where('sales_orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc') // 按总金额降序排列
            ->get();

        return response()->json($report);
    }

    /**
     * 销售趋势报表
     *
     * @queryParam start_date date 开始日期（默认最近6个月） Example: 2024-01-01
     * @queryParam end_date date 结束日期（默认今天） Example: 2024-06-30
     * @queryParam group_by string 分组方式（day/week/month，默认day） Example: month
     * @param Request $request 请求对象，支持 start_date（开始日期）、end_date（结束日期）和 group_by（分组方式：day/week/month）
     * @return \Illuminate\Http\JsonResponse 返回按时间段分组的销售趋势数据（订单数和总金额）
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

        // 按时间段分组统计销售趋势
        $report = SalesOrder::selectRaw("DATE_FORMAT(order_date, '{$format}') as period")
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
