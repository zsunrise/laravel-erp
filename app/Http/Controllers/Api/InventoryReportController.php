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
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 查询库存周转率数据
        $report = Inventory::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(inventories.quantity) as current_quantity')         // 当前数量
            ->selectRaw('SUM(inventories.available_quantity) as available_quantity') // 可用数量
            ->selectRaw('SUM(inventories.quantity * inventories.average_cost) as total_value') // 总价值
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.quantity ELSE 0 END), 0) as out_quantity') // 出库数量
            ->join('products', 'inventories.product_id', '=', 'products.id') // 关联产品表
            ->leftJoin('inventory_transactions', function($join) use ($startDate, $endDate) {
                // 关联指定日期范围内的交易记录
                $join->on('inventory_transactions.product_id', '=', 'inventories.product_id')
                     ->on('inventory_transactions.warehouse_id', '=', 'inventories.warehouse_id')
                     ->whereBetween('inventory_transactions.transaction_date', [$startDate, $endDate]);
            })
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->havingRaw('out_quantity > 0') // 只显示有出库记录的产品
            ->get()
            ->map(function($item) {
                // 计算平均库存和周转率
                $avgInventory = ($item->current_quantity + ($item->current_quantity - $item->out_quantity)) / 2;
                $item->turnover_rate = $avgInventory > 0 ? ($item->out_quantity / $avgInventory) * 30 : 0; // 周转率
                $item->turnover_days = $item->turnover_rate > 0 ? (30 / $item->turnover_rate) : 0; // 周转天数
                return $item;
            })
            ->sortByDesc('turnover_rate') // 按周转率降序排列
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
        // 获取天数阈值，默认90天
        $days = $request->days ?? 90;

        // 查询呆滞库存数据
        $report = Inventory::select('products.id', 'products.name', 'products.sku')
            ->selectRaw('SUM(inventories.quantity) as current_quantity')         // 当前数量
            ->selectRaw('SUM(inventories.quantity * inventories.average_cost) as total_value') // 总价值
            ->selectRaw('MAX(inventory_transactions.transaction_date) as last_transaction_date') // 最后交易日期
            ->join('products', 'inventories.product_id', '=', 'products.id') // 关联产品表
            ->leftJoin('inventory_transactions', function($join) {
                // 关联所有交易记录
                $join->on('inventory_transactions.product_id', '=', 'inventories.product_id')
                     ->on('inventory_transactions.warehouse_id', '=', 'inventories.warehouse_id');
            })
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->get()
            ->map(function($item) use ($days) {
                // 计算距离最后交易的天数
                $item->days_since_last_transaction = $item->last_transaction_date 
                    ? now()->diffInDays($item->last_transaction_date) 
                    : 999; // 无交易记录则设为999天
                return $item;
            })
            ->filter(function($item) use ($days) {
                // 筛选超过指定天数未发生交易的产品
                return $item->days_since_last_transaction > $days;
            })
            ->sortByDesc('days_since_last_transaction') // 按未交易天数降序排列
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
        // 获取仓库ID筛选参数
        $warehouseId = $request->warehouse_id;
        
        // 构建库存估值查询
        $query = Inventory::select('inventories.id', 'inventories.product_id', 'inventories.warehouse_id', 'inventories.quantity')
            ->selectRaw('inventories.quantity * inventories.average_cost as total_value') // 计算库存价值
            ->with(['product', 'warehouse']); // 预加载产品和仓库信息

        // 按仓库筛选
        if ($warehouseId) {
            $query->where('inventories.warehouse_id', $warehouseId);
        }

        // 执行查询并格式化结果
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
                    'quantity' => $item->quantity,       // 数量
                    'total_value' => $item->total_value ?? 0, // 总价值
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
        // 获取日期范围，默认为本月
        $startDate = $request->start_date ?? now()->startOfMonth()->toDateString();
        $endDate = $request->end_date ?? now()->toDateString();

        // 按产品分组统计库存变动
        $report = InventoryTransaction::select('products.id', 'products.name', 'products.sku')
            // 入库数量和金额
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "in" THEN inventory_transactions.quantity ELSE 0 END) as in_quantity')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "in" THEN inventory_transactions.total_cost ELSE 0 END) as in_amount')
            // 出库数量和金额
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.quantity ELSE 0 END) as out_quantity')
            ->selectRaw('SUM(CASE WHEN inventory_transactions.type = "out" THEN inventory_transactions.total_cost ELSE 0 END) as out_amount')
            ->join('products', 'inventory_transactions.product_id', '=', 'products.id') // 关联产品表
            ->whereBetween('inventory_transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('in_quantity', 'desc') // 按入库数量降序排列
            ->get();

        return response()->json($report);
    }
}
