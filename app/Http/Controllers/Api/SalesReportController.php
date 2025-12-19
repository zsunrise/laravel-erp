<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function summary(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $query = SalesOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $summary = [
            'total_orders' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_quantity' => SalesOrderItem::whereHas('salesOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('order_date', [$startDate, $endDate])
                  ->where('status', '!=', 'cancelled');
            })->sum('quantity'),
            'average_order_amount' => $query->avg('total_amount'),
        ];

        return response()->json($summary);
    }

    public function byCustomer(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = SalesOrder::select('customers.id', 'customers.name', 'customers.code')
            ->selectRaw('COUNT(sales_orders.id) as order_count')
            ->selectRaw('SUM(sales_orders.total_amount) as total_amount')
            ->selectRaw('AVG(sales_orders.total_amount) as avg_amount')
            ->join('customers', 'sales_orders.customer_id', '=', 'customers.id')
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->where('sales_orders.status', '!=', 'cancelled')
            ->groupBy('customers.id', 'customers.name', 'customers.code')
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json($report);
    }

    public function byProduct(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = SalesOrderItem::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(sales_order_items.quantity) as total_quantity')
            ->selectRaw('SUM(sales_order_items.total_amount) as total_amount')
            ->selectRaw('AVG(sales_order_items.unit_price) as avg_price')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->join('sales_orders', 'sales_order_items.sales_order_id', '=', 'sales_orders.id')
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->where('sales_orders.status', '!=', 'cancelled')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_amount', 'desc')
            ->get();

        return response()->json($report);
    }

    public function trend(Request $request)
    {
        $startDate = $request->start_date ?? now()->subMonths(6)->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();
        $groupBy = $request->group_by ?? 'day'; // day, week, month

        $format = match($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $report = SalesOrder::selectRaw("DATE_FORMAT(order_date, '{$format}') as period")
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
