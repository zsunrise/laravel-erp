<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Models\ApprovalRecord;
use App\Models\AccountsReceivable;
use App\Models\AccountsPayable;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * 获取仪表盘统计数据
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回仪表盘统计数据，包括销售订单、采购订单、库存、营收、待审批、工单、应收应付账款和销售趋势等
     */
    public function getStats(Request $request)
    {
        try {
            $now = Carbon::now();
            $currentMonth = $now->startOfMonth();
            $lastMonth = $now->copy()->subMonth()->startOfMonth();

            // 销售订单统计
            $salesOrders = SalesOrder::where('order_date', '>=', $currentMonth)->count();
            $salesOrdersLastMonth = SalesOrder::where('order_date', '>=', $lastMonth)
                ->where('order_date', '<', $currentMonth)
                ->count();
            $salesOrdersTrend = $salesOrdersLastMonth > 0 
                ? round((($salesOrders - $salesOrdersLastMonth) / $salesOrdersLastMonth) * 100, 1)
                : 0;

            // 采购订单统计
            $purchaseOrders = PurchaseOrder::where('order_date', '>=', $currentMonth)->count();
            $purchaseOrdersLastMonth = PurchaseOrder::where('order_date', '>=', $lastMonth)
                ->where('order_date', '<', $currentMonth)
                ->count();
            $purchaseOrdersTrend = $purchaseOrdersLastMonth > 0 
                ? round((($purchaseOrders - $purchaseOrdersLastMonth) / $purchaseOrdersLastMonth) * 100, 1)
                : 0;

            // 库存商品统计（有库存的商品种类）
            $inventoryCount = Inventory::where('quantity', '>', 0)->count();
            
            // 库存预警（低于最小库存的商品）
            $lowStockCount = 0;
            $productsWithMinStock = Product::where('min_stock', '>', 0)
                ->with('inventory')
                ->get();
            foreach ($productsWithMinStock as $product) {
                $totalQuantity = $product->inventory->sum('quantity');
                if ($totalQuantity < $product->min_stock) {
                    $lowStockCount++;
                }
            }

            // 本月营收
            $revenue = SalesOrder::where('order_date', '>=', $currentMonth)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            $revenueLastMonth = SalesOrder::where('order_date', '>=', $lastMonth)
                ->where('order_date', '<', $currentMonth)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');
            $revenueTrend = $revenueLastMonth > 0 
                ? round((($revenue - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                : 0;

            // 待审批数量
            $pendingApprovals = WorkflowInstance::where('status', 'pending')->count();

            // 生产工单统计
            $workOrders = WorkOrder::whereIn('status', ['pending', 'in_progress'])->count();

            // 应收账款（未结清的）
            $receivables = AccountsReceivable::whereIn('status', ['outstanding', 'partial', 'overdue'])
                ->sum('remaining_amount');

            // 应付账款（未结清的）
            $payables = AccountsPayable::whereIn('status', ['outstanding', 'partial', 'overdue'])
                ->sum('remaining_amount');

            // 近7天销售趋势
            $salesTrend = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->toDateString();
                $amount = SalesOrder::whereDate('order_date', $date)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount');
                $salesTrend[] = [
                    'date' => $date,
                    'amount' => $amount
                ];
            }

            return response()->json([
                'stats' => [
                    'salesOrders' => [
                        'value' => $salesOrders,
                        'trend' => $salesOrdersTrend,
                        'label' => '销售订单'
                    ],
                    'purchaseOrders' => [
                        'value' => $purchaseOrders,
                        'trend' => $purchaseOrdersTrend,
                        'label' => '采购订单'
                    ],
                    'inventory' => [
                        'value' => $inventoryCount,
                        'warning' => $lowStockCount,
                        'label' => '库存商品'
                    ],
                    'revenue' => [
                        'value' => $revenue,
                        'trend' => $revenueTrend,
                        'label' => '本月营收',
                        'formatted' => '¥' . number_format($revenue, 2)
                    ],
                    'pendingApprovals' => [
                        'value' => $pendingApprovals,
                        'label' => '待审批'
                    ],
                    'workOrders' => [
                        'value' => $workOrders,
                        'label' => '生产工单'
                    ],
                    'receivables' => [
                        'value' => $receivables,
                        'formatted' => '¥' . number_format($receivables, 2),
                        'label' => '应收账款'
                    ],
                    'payables' => [
                        'value' => $payables,
                        'formatted' => '¥' . number_format($payables, 2),
                        'label' => '应付账款'
                    ]
                ],
                'salesTrend' => $salesTrend
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '获取统计数据失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取待处理事项
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回待处理事项列表，包括待审批工作流、低库存预警和逾期应收账款等
     */
    public function getPendingTasks(Request $request)
    {
        try {
            $tasks = [];

            // 待审批工作流
            $pendingWorkflows = WorkflowInstance::with(['workflow', 'initiator'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($pendingWorkflows as $workflow) {
                $tasks[] = [
                    'id' => $workflow->id,
                    'type' => '审批',
                    'title' => $workflow->workflow->name . ' - ' . $workflow->business_key,
                    'date' => $workflow->created_at->format('Y-m-d'),
                    'module' => 'workflow',
                    'priority' => 'normal'
                ];
            }

            // 低库存预警
            $lowStockProducts = Product::where('min_stock', '>', 0)
                ->with('inventory')
                ->get()
                ->filter(function($product) {
                    $totalQuantity = $product->inventory->sum('quantity');
                    return $totalQuantity < $product->min_stock;
                })
                ->take(5);

            foreach ($lowStockProducts as $product) {
                $totalQuantity = $product->inventory->sum('quantity');
                $tasks[] = [
                    'id' => $product->id,
                    'type' => '预警',
                    'title' => $product->name . ' 库存不足 (当前: ' . $totalQuantity . ', 最小: ' . $product->min_stock . ')',
                    'date' => Carbon::now()->format('Y-m-d'),
                    'module' => 'inventory',
                    'priority' => 'high'
                ];
            }

            // 逾期应收账款
            $overdueReceivables = AccountsReceivable::with('customer')
                ->where('status', 'overdue')
                ->where('remaining_amount', '>', 0)
                ->orderBy('due_date', 'asc')
                ->limit(5)
                ->get();

            foreach ($overdueReceivables as $receivable) {
                $daysOverdue = Carbon::now()->diffInDays($receivable->due_date);
                $tasks[] = [
                    'id' => $receivable->id,
                    'type' => '逾期',
                    'title' => '应收账款逾期 - ' . ($receivable->customer->name ?? '未知客户') . ' (逾期' . $daysOverdue . '天)',
                    'date' => $receivable->due_date->format('Y-m-d'),
                    'module' => 'receivable',
                    'priority' => 'high'
                ];
            }

            // 按优先级和日期排序
            usort($tasks, function($a, $b) {
                $priorityWeight = ['high' => 3, 'normal' => 2, 'low' => 1];
                $priorityDiff = ($priorityWeight[$b['priority']] ?? 0) - ($priorityWeight[$a['priority']] ?? 0);
                if ($priorityDiff !== 0) {
                    return $priorityDiff;
                }
                return strcmp($b['date'], $a['date']);
            });

            return response()->json([
                'tasks' => array_slice($tasks, 0, 10)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '获取待处理事项失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取最近订单
     *
     * @param Request $request 请求对象
     * @return \Illuminate\Http\JsonResponse 返回最近的销售订单和采购订单列表
     */
    public function getRecentOrders(Request $request)
    {
        try {
            $orders = [];

            // 最近销售订单
            $salesOrders = SalesOrder::with('customer')
                ->orderBy('order_date', 'desc')
                ->limit(5)
                ->get();

            foreach ($salesOrders as $order) {
                $orders[] = [
                    'id' => $order->id,
                    'orderNo' => $order->order_no,
                    'type' => '销售',
                    'customer' => $order->customer->name ?? '-',
                    'amount' => '¥' . number_format($order->total_amount, 2),
                    'status' => $this->translateStatus($order->status),
                    'date' => $order->order_date->format('Y-m-d'),
                    'module' => 'sales'
                ];
            }

            // 最近采购订单
            $purchaseOrders = PurchaseOrder::with('supplier')
                ->orderBy('order_date', 'desc')
                ->limit(5)
                ->get();

            foreach ($purchaseOrders as $order) {
                $orders[] = [
                    'id' => $order->id,
                    'orderNo' => $order->order_no,
                    'type' => '采购',
                    'customer' => $order->supplier->name ?? '-',
                    'amount' => '¥' . number_format($order->total_amount, 2),
                    'status' => $this->translateStatus($order->status),
                    'date' => $order->order_date->format('Y-m-d'),
                    'module' => 'purchase'
                ];
            }

            // 按日期排序
            usort($orders, function($a, $b) {
                return strcmp($b['date'], $a['date']);
            });

            return response()->json([
                'orders' => array_slice($orders, 0, 10)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '获取最近订单失败',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 翻译状态
     */
    private function translateStatus($status)
    {
        $statusMap = [
            'draft' => '草稿',
            'pending' => '待审核',
            'approved' => '已审核',
            'in_progress' => '进行中',
            'completed' => '已完成',
            'cancelled' => '已取消',
            'rejected' => '已拒绝'
        ];

        return $statusMap[$status] ?? $status;
    }
}

