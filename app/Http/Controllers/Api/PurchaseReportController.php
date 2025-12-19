<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PurchaseReportController extends Controller
{
    public function summary(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $query = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled');

        $summary = [
            'total_orders' => $query->count(),
            'total_amount' => $query->sum('total_amount'),
            'total_quantity' => PurchaseOrderItem::whereHas('purchaseOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('order_date', [$startDate, $endDate])
                  ->where('status', '!=', 'cancelled');
            })->sum('quantity'),
            'average_order_amount' => $query->avg('total_amount'),
        ];

        return response()->json($summary);
    }

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
