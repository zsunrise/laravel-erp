<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryReportController extends Controller
{
    /**
     * 库存周转率报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回产品的库存周转率和周转天数统计，按周转率降序排列
     */
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

    /**
     * 呆滞库存报表
     *
     * @param Request $request 请求对象，支持 days（天数阈值，默认90天）
     * @return \Illuminate\Http\JsonResponse 返回超过指定天数未发生交易的产品库存信息，按未交易天数降序排列
     */
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

    /**
     * 库存估值报表
     *
     * @param Request $request 请求对象，支持 warehouse_id（仓库ID）
     * @return \Illuminate\Http\JsonResponse 返回按产品和仓库分组的库存估值数据（数量和总价值）
     */
    public function valuation(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        
        $query = Inventory::select('inventories.id', 'inventories.product_id', 'inventories.warehouse_id', 'inventories.quantity')
            ->selectRaw('inventories.quantity * inventories.average_cost as total_value')
            ->with(['product', 'warehouse']);

        if ($warehouseId) {
            $query->where('inventories.warehouse_id', $warehouseId);
        }

        $report = $query->get()
            ->map(function($item) {
                return [
                    'product' => [
                        'id' => $item->product_id,
                        'name' => $item->product->name ?? '',
                        'sku' => $item->product->sku ?? '',
                    ],
                    'warehouse' => [
                        'id' => $item->warehouse_id,
                        'name' => $item->warehouse->name ?? '',
                    ],
                    'quantity' => $item->quantity,
                    'total_value' => $item->total_value ?? 0,
                ];
            });

        return ApiResponse::success($report, '获取成功');
    }

    /**
     * 库存变动报表
     *
     * @param Request $request 请求对象，支持 start_date（开始日期）和 end_date（结束日期）
     * @return \Illuminate\Http\JsonResponse 返回按产品分组的库存变动统计（入库数量/金额、出库数量/金额），按入库数量降序排列
     */
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
