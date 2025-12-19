<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    public function turnover(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = Inventory::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(inventories.quantity) as current_quantity')
            ->selectRaw('SUM(inventories.available_quantity) as available_quantity')
            ->selectRaw('SUM(inventories.quantity * inventories.average_cost) as total_value')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.quantity ELSE 0 END), 0) as out_quantity')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('inventory_transactions', function($join) use ($startDate, $endDate) {
                $join->on('inventory_transactions.product_id', '=', 'inventories.product_id')
                     ->on('inventory_transactions.warehouse_id', '=', 'inventories.warehouse_id')
                     ->whereBetween('inventory_transactions.transaction_date', [$startDate, $endDate]);
            })
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->havingRaw('out_quantity > 0')
            ->get()
            ->map(function($item) {
                $avgInventory = ($item->current_quantity + ($item->current_quantity - $item->out_quantity)) / 2;
                $item->turnover_rate = $avgInventory > 0 ? ($item->out_quantity / $avgInventory) * 30 : 0;
                $item->turnover_days = $item->turnover_rate > 0 ? (30 / $item->turnover_rate) : 0;
                return $item;
            })
            ->sortByDesc('turnover_rate')
            ->values();

        return response()->json($report);
    }

    public function slowMoving(Request $request)
    {
        $days = $request->days ?? 90;

        $report = Inventory::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(inventories.quantity) as current_quantity')
            ->selectRaw('SUM(inventories.quantity * inventories.average_cost) as total_value')
            ->selectRaw('MAX(inventory_transactions.transaction_date) as last_transaction_date')
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('inventory_transactions', function($join) {
                $join->on('inventory_transactions.product_id', '=', 'inventories.product_id')
                     ->on('inventory_transactions.warehouse_id', '=', 'inventories.warehouse_id');
            })
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get()
            ->map(function($item) use ($days) {
                $item->days_since_last_transaction = $item->last_transaction_date 
                    ? now()->diffInDays($item->last_transaction_date) 
                    : 999;
                return $item;
            })
            ->filter(function($item) use ($days) {
                return $item->days_since_last_transaction > $days;
            })
            ->sortByDesc('days_since_last_transaction')
            ->values();

        return response()->json($report);
    }

    public function valuation(Request $request)
    {
        $report = Inventory::select('warehouses.id', 'warehouses.name')
            ->selectRaw('COUNT(DISTINCT inventories.product_id) as product_count')
            ->selectRaw('SUM(inventories.quantity) as total_quantity')
            ->selectRaw('SUM(inventories.quantity * inventories.average_cost) as total_value')
            ->join('warehouses', 'inventories.warehouse_id', '=', 'warehouses.id')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderBy('total_value', 'desc')
            ->get();

        return response()->json($report);
    }

    public function movement(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        $report = InventoryTransaction::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "in" THEN inventory_transactions.quantity ELSE 0 END) as in_quantity')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.quantity ELSE 0 END) as out_quantity')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "in" THEN inventory_transactions.total_cost ELSE 0 END) as in_amount')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.total_cost ELSE 0 END) as out_amount')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id')
            ->whereBetween('inventory_transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('in_quantity', 'desc')
            ->get();

        return response()->json($report);
    }
}
