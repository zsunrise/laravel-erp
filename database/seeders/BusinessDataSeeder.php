<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\ProcessRoute;
use App\Models\ProcessRouteStep;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseSettlement;
use App\Models\PurchaseSettlementItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\SalesSettlement;
use App\Models\SalesSettlementItem;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\ProductionMaterialIssue;
use App\Models\ProductionMaterialIssueItem;
use App\Models\ProductionReport;
use App\Models\InventoryTransaction;
use App\Models\AccountingVoucher;
use App\Models\AccountingVoucherItem;
use App\Models\AccountsReceivable;
use App\Models\AccountsPayable;
use App\Models\ChartOfAccount;
use App\Models\Workflow;
use App\Models\WorkflowNode;
use App\Models\WorkflowInstance;
use App\Models\ApprovalRecord;
use App\Models\Unit;
use App\Models\Currency;
use App\Models\Region;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessDataSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            $this->createProducts();
            $this->createSuppliersAndCustomers();
            $this->createBomsAndRoutes();
            $this->createPurchaseData();
            $this->createSalesData();
            $this->createProductionData();
            $this->createInventoryTransactions();
            $this->createFinancialData();
            $this->createWorkflows();
        });
    }

    private function createProducts()
    {
        $unit = Unit::first();
        $currency = Currency::where('code', 'CNY')->first();
        
        $category = ProductCategory::create([
            'name' => '电子产品',
            'code' => 'ELEC',
            'sort' => 1,
            'is_active' => true,
        ]);

        $products = [
            ['name' => '智能手机', 'sku' => 'P001', 'barcode' => '6901234567890', 'purchase_price' => 800, 'sale_price' => 1200, 'cost_price' => 850, 'min_stock' => 50, 'max_stock' => 500],
            ['name' => '平板电脑', 'sku' => 'P002', 'barcode' => '6901234567891', 'purchase_price' => 1200, 'sale_price' => 1800, 'cost_price' => 1250, 'min_stock' => 30, 'max_stock' => 300],
            ['name' => '笔记本电脑', 'sku' => 'P003', 'barcode' => '6901234567892', 'purchase_price' => 3500, 'sale_price' => 5000, 'cost_price' => 3600, 'min_stock' => 20, 'max_stock' => 200],
            ['name' => '手机屏幕', 'sku' => 'P004', 'barcode' => '6901234567893', 'purchase_price' => 150, 'sale_price' => 250, 'cost_price' => 160, 'min_stock' => 100, 'max_stock' => 1000],
            ['name' => '电池', 'sku' => 'P005', 'barcode' => '6901234567894', 'purchase_price' => 50, 'sale_price' => 80, 'cost_price' => 55, 'min_stock' => 200, 'max_stock' => 2000],
            ['name' => '充电器', 'sku' => 'P006', 'barcode' => '6901234567895', 'purchase_price' => 30, 'sale_price' => 50, 'cost_price' => 35, 'min_stock' => 300, 'max_stock' => 3000],
        ];

        foreach ($products as $productData) {
            Product::create(array_merge($productData, [
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'is_active' => true,
            ]));
        }
    }

    private function createSuppliersAndCustomers()
    {
        $region = Region::first();
        $currency = Currency::where('code', 'CNY')->first();

        $suppliers = [
            ['code' => 'SUP001', 'name' => '深圳电子供应商', 'contact_person' => '张经理', 'contact_phone' => '13800000001', 'email' => 'supplier1@example.com', 'address' => '深圳市南山区', 'rating' => 'A', 'credit_limit' => 100000, 'payment_days' => 30],
            ['code' => 'SUP002', 'name' => '广州配件供应商', 'contact_person' => '李经理', 'contact_phone' => '13800000002', 'email' => 'supplier2@example.com', 'address' => '广州市天河区', 'rating' => 'B', 'credit_limit' => 50000, 'payment_days' => 45],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::create(array_merge($supplierData, [
                'region_id' => $region->id,
                'is_active' => true,
            ]));
        }

        $customers = [
            ['code' => 'CUS001', 'name' => '北京贸易公司', 'contact_person' => '王总', 'contact_phone' => '13900000001', 'email' => 'customer1@example.com', 'address' => '北京市朝阳区', 'rating' => 'A', 'credit_limit' => 200000, 'payment_days' => 30],
            ['code' => 'CUS002', 'name' => '上海科技公司', 'contact_person' => '赵总', 'contact_phone' => '13900000002', 'email' => 'customer2@example.com', 'address' => '上海市浦东新区', 'rating' => 'A', 'credit_limit' => 150000, 'payment_days' => 45],
        ];

        foreach ($customers as $customerData) {
            Customer::create(array_merge($customerData, [
                'region_id' => $region->id,
                'is_active' => true,
            ]));
        }
    }

    private function createBomsAndRoutes()
    {
        $products = Product::all();
        $admin = User::where('email', 'admin@example.com')->first();

        if ($products->count() >= 3) {
            $product = $products->first();
            $component1 = $products->skip(3)->first();
            $component2 = $products->skip(4)->first();

            if ($component1 && $component2) {
                $bom = Bom::create([
                    'product_id' => $product->id,
                    'version' => '1.0',
                    'effective_date' => now()->subDays(30),
                    'is_default' => true,
                    'is_active' => true,
                    'description' => '智能手机BOM',
                    'created_by' => $admin->id,
                ]);

                BomItem::create([
                    'bom_id' => $bom->id,
                    'component_product_id' => $component1->id,
                    'quantity' => 1,
                    'sequence' => 1,
                ]);

                BomItem::create([
                    'bom_id' => $bom->id,
                    'component_product_id' => $component2->id,
                    'quantity' => 2,
                    'sequence' => 2,
                ]);

                $processRoute = ProcessRoute::create([
                    'product_id' => $product->id,
                    'version' => '1.0',
                    'effective_date' => now()->subDays(30),
                    'is_default' => true,
                    'is_active' => true,
                    'description' => '智能手机工艺路线',
                    'created_by' => $admin->id,
                ]);

                ProcessRouteStep::create([
                    'process_route_id' => $processRoute->id,
                    'step_name' => '组装',
                    'sequence' => 1,
                    'standard_time' => 120,
                    'setup_time' => 30,
                    'queue_time' => 10,
                    'move_time' => 5,
                ]);

                ProcessRouteStep::create([
                    'process_route_id' => $processRoute->id,
                    'step_name' => '测试',
                    'sequence' => 2,
                    'standard_time' => 60,
                    'setup_time' => 15,
                    'queue_time' => 5,
                    'move_time' => 3,
                ]);
            }
        }
    }

    private function createPurchaseData()
    {
        $supplier = Supplier::first();
        $warehouse = Warehouse::first();
        $currency = Currency::where('code', 'CNY')->first();
        $products = Product::take(3)->get();
        $admin = User::where('email', 'admin@example.com')->first();

        if ($supplier && $warehouse && $products->count() >= 3) {
            $order = PurchaseOrder::create([
                'order_no' => 'PO' . date('Ymd') . '001',
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->subDays(20),
                'expected_date' => now()->subDays(10),
                'status' => 'completed',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(19),
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($products as $index => $product) {
                $quantity = [100, 50, 30][$index] ?? 20;
                $unitPrice = $product->purchase_price;
                $taxRate = 13;
                $item = PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'received_quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                ]);
                $subtotal += $item->subtotal;
                $taxAmount += $item->tax_amount;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            $return = PurchaseReturn::create([
                'return_no' => 'PR' . date('Ymd') . '001',
                'purchase_order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'return_date' => now()->subDays(5),
                'status' => 'approved',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(4),
            ]);

            $returnItem = PurchaseReturnItem::create([
                'purchase_return_id' => $return->id,
                'product_id' => $products->first()->id,
                'quantity' => 5,
                'unit_price' => $products->first()->purchase_price,
                'tax_rate' => 13,
            ]);

            $return->update([
                'subtotal' => $returnItem->subtotal,
                'tax_amount' => $returnItem->tax_amount,
                'total_amount' => $returnItem->subtotal + $returnItem->tax_amount,
            ]);

            $settlement = PurchaseSettlement::create([
                'settlement_no' => 'PS' . date('Ymd') . '001',
                'supplier_id' => $supplier->id,
                'settlement_date' => now()->subDays(3),
                'status' => 'approved',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(2),
            ]);

            $settlementAmount = $order->total_amount - $return->total_amount;
            
            PurchaseSettlementItem::create([
                'purchase_settlement_id' => $settlement->id,
                'reference_type' => 'App\Models\PurchaseOrder',
                'reference_id' => $order->id,
                'reference_no' => $order->order_no,
                'amount' => $settlementAmount,
            ]);

            $settlement->update([
                'total_amount' => $settlementAmount,
                'paid_amount' => $settlementAmount,
            ]);
        }
    }

    private function createSalesData()
    {
        $customer = Customer::first();
        $warehouse = Warehouse::first();
        $currency = Currency::where('code', 'CNY')->first();
        $products = Product::take(3)->get();
        $admin = User::where('email', 'admin@example.com')->first();

        if ($customer && $warehouse && $products->count() >= 3) {
            $order = SalesOrder::create([
                'order_no' => 'SO' . date('Ymd') . '001',
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->subDays(15),
                'delivery_date' => now()->subDays(5),
                'status' => 'completed',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(14),
            ]);

            $subtotal = 0;
            $taxAmount = 0;

            foreach ($products as $index => $product) {
                $quantity = [50, 30, 20][$index] ?? 10;
                $unitPrice = $product->sale_price;
                $taxRate = 13;
                $item = SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'shipped_quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                ]);
                $subtotal += $item->subtotal;
                $taxAmount += $item->tax_amount;
            }

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            $return = SalesReturn::create([
                'return_no' => 'SR' . date('Ymd') . '001',
                'sales_order_id' => $order->id,
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'return_date' => now()->subDays(2),
                'status' => 'approved',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(1),
            ]);

            $returnItem = SalesReturnItem::create([
                'sales_return_id' => $return->id,
                'product_id' => $products->first()->id,
                'quantity' => 2,
                'unit_price' => $products->first()->sale_price,
                'tax_rate' => 13,
            ]);

            $return->update([
                'subtotal' => $returnItem->subtotal,
                'tax_amount' => $returnItem->tax_amount,
                'total_amount' => $returnItem->subtotal + $returnItem->tax_amount,
            ]);

            $settlement = SalesSettlement::create([
                'settlement_no' => 'SS' . date('Ymd') . '001',
                'customer_id' => $customer->id,
                'settlement_date' => now()->subDays(1),
                'status' => 'approved',
                'currency_id' => $currency->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $settlementAmount = $order->total_amount - $return->total_amount;
            
            SalesSettlementItem::create([
                'sales_settlement_id' => $settlement->id,
                'reference_type' => 'App\Models\SalesOrder',
                'reference_id' => $order->id,
                'reference_no' => $order->order_no,
                'amount' => $settlementAmount,
            ]);

            $settlement->update([
                'total_amount' => $settlementAmount,
                'received_amount' => $settlementAmount,
            ]);
        }
    }

    private function createProductionData()
    {
        $product = Product::first();
        $warehouse = Warehouse::first();
        $bom = Bom::first();
        $processRoute = ProcessRoute::first();
        $admin = User::where('email', 'admin@example.com')->first();
        $operator = User::where('email', 'operator@example.com')->first();

        if ($product && $warehouse && $bom && $processRoute) {
            $plan = ProductionPlan::create([
                'plan_no' => 'PP' . date('Ymd') . '001',
                'plan_date' => now()->subDays(10),
                'start_date' => now()->subDays(8),
                'end_date' => now()->subDays(1),
                'status' => 'approved',
                'warehouse_id' => $warehouse->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(9),
            ]);

            $planItem = ProductionPlanItem::create([
                'production_plan_id' => $plan->id,
                'product_id' => $product->id,
                'bom_id' => $bom->id,
                'process_route_id' => $processRoute->id,
                'planned_quantity' => 100,
                'planned_start_date' => now()->subDays(8),
                'planned_end_date' => now()->subDays(1),
                'priority' => 1,
            ]);

            $workOrder = WorkOrder::create([
                'work_order_no' => 'WO' . date('Ymd') . '001',
                'production_plan_id' => $plan->id,
                'production_plan_item_id' => $planItem->id,
                'product_id' => $product->id,
                'bom_id' => $bom->id,
                'process_route_id' => $processRoute->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => 100,
                'completed_quantity' => 95,
                'start_date' => now()->subDays(8),
                'planned_end_date' => now()->subDays(1),
                'actual_end_date' => now(),
                'status' => 'completed',
                'assigned_to' => $operator->id,
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => now()->subDays(7),
            ]);

            if ($processRoute->steps->count() > 0) {
                foreach ($processRoute->steps as $index => $step) {
                    $plannedTime = $step->standard_time + $step->setup_time;
                    $startDate = now()->subDays(8)->addDays($index);
                    $endDate = now()->subDays(8)->addDays($index);
                    
                    WorkOrderItem::create([
                        'work_order_id' => $workOrder->id,
                        'process_route_step_id' => $step->id,
                        'step_name' => $step->step_name,
                        'sequence' => $step->sequence,
                        'planned_time' => $plannedTime,
                        'actual_time' => $plannedTime,
                        'planned_start_date' => $startDate,
                        'planned_end_date' => $endDate,
                        'actual_start_date' => $startDate,
                        'actual_end_date' => $endDate,
                        'status' => 'completed',
                    ]);
                }
            }

            $materialIssue = ProductionMaterialIssue::create([
                'issue_no' => 'MI' . date('Ymd') . '001',
                'work_order_id' => $workOrder->id,
                'warehouse_id' => $warehouse->id,
                'issue_date' => now()->subDays(7),
                'status' => 'completed',
                'created_by' => $admin->id,
            ]);

            if ($bom->items->count() > 0) {
                foreach ($bom->items as $bomItem) {
                    $product = Product::find($bomItem->component_product_id);
                    ProductionMaterialIssueItem::create([
                        'material_issue_id' => $materialIssue->id,
                        'product_id' => $bomItem->component_product_id,
                        'quantity' => $bomItem->quantity * $workOrder->quantity,
                        'unit_cost' => $product ? $product->cost_price : 0,
                    ]);
                }
            }

            ProductionReport::create([
                'report_no' => 'PR' . date('Ymd') . '001',
                'work_order_id' => $workOrder->id,
                'report_date' => now()->subDays(1),
                'quantity' => 95,
                'qualified_quantity' => 90,
                'defective_quantity' => 5,
                'work_hours' => 8,
                'reported_by' => $operator->id,
                'remark' => '生产报工',
            ]);
        }
    }

    private function createInventoryTransactions()
    {
        $warehouse = Warehouse::first();
        $products = Product::take(3)->get();
        $admin = User::where('email', 'admin@example.com')->first();

        if ($warehouse && $products->count() >= 3) {
            foreach ($products as $index => $product) {
                $quantities = [100, 50, 30];
                $quantity = $quantities[$index] ?? 20;
                $unitCost = $product->purchase_price;
                InventoryTransaction::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'type' => 'in',
                    'reference_type' => 'App\Models\PurchaseOrder',
                    'reference_id' => PurchaseOrder::first()->id ?? 1,
                    'reference_no' => 'PO' . date('Ymd') . '001',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $quantity * $unitCost,
                    'transaction_date' => now()->subDays(10),
                    'user_id' => $admin->id,
                ]);
            }
        }
    }

    private function createFinancialData()
    {
        $currency = Currency::where('code', 'CNY')->first();
        $admin = User::where('email', 'admin@example.com')->first();
        $customer = Customer::first();
        $supplier = Supplier::first();
        $purchaseOrder = PurchaseOrder::first();
        $salesOrder = SalesOrder::first();

        if ($currency && $admin) {
            $cashAccount = ChartOfAccount::where('code', '100101')->first();
            $bankAccount = ChartOfAccount::where('code', '100102')->first();

            if ($cashAccount && $bankAccount) {
                $voucher = AccountingVoucher::create([
                    'voucher_no' => 'V' . date('Ymd') . '001',
                    'voucher_date' => now()->subDays(5),
                    'type' => 'general',
                    'status' => 'posted',
                    'created_by' => $admin->id,
                    'posted_by' => $admin->id,
                    'posted_at' => now()->subDays(4),
                ]);

                AccountingVoucherItem::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $cashAccount->id,
                    'direction' => 'debit',
                    'amount' => 10000,
                    'sequence' => 1,
                ]);

                AccountingVoucherItem::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $bankAccount->id,
                    'direction' => 'credit',
                    'amount' => 10000,
                    'sequence' => 2,
                ]);
            }

            if ($customer && $salesOrder) {
                AccountsReceivable::create([
                    'customer_id' => $customer->id,
                    'reference_type' => 'App\Models\SalesOrder',
                    'reference_id' => $salesOrder->id,
                    'reference_no' => $salesOrder->order_no,
                    'invoice_date' => now()->subDays(10),
                    'due_date' => now()->addDays(20),
                    'original_amount' => $salesOrder->total_amount,
                    'received_amount' => 0,
                    'remaining_amount' => $salesOrder->total_amount,
                    'status' => 'outstanding',
                    'currency_id' => $currency->id,
                ]);
            }

            if ($supplier && $purchaseOrder) {
                AccountsPayable::create([
                    'supplier_id' => $supplier->id,
                    'reference_type' => 'App\Models\PurchaseOrder',
                    'reference_id' => $purchaseOrder->id,
                    'reference_no' => $purchaseOrder->order_no,
                    'invoice_date' => now()->subDays(15),
                    'due_date' => now()->addDays(15),
                    'original_amount' => $purchaseOrder->total_amount,
                    'paid_amount' => 0,
                    'remaining_amount' => $purchaseOrder->total_amount,
                    'status' => 'outstanding',
                    'currency_id' => $currency->id,
                ]);
            }
        }
    }

    private function createWorkflows()
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $manager = User::where('email', 'manager@example.com')->first();

        if ($admin && $manager) {
            $workflow = Workflow::create([
                'name' => '采购订单审批流程',
                'code' => 'purchase_order_approval',
                'type' => 'purchase_order',
                'description' => '采购订单审批流程',
                'is_active' => true,
                'version' => 1,
                'created_by' => $admin->id,
            ]);

            WorkflowNode::create([
                'workflow_id' => $workflow->id,
                'node_name' => '部门经理审批',
                'node_type' => 'approval',
                'approval_type' => 'user',
                'approver_config' => ['user_id' => $manager->id],
                'sequence' => 1,
                'is_required' => true,
            ]);

            WorkflowNode::create([
                'workflow_id' => $workflow->id,
                'node_name' => '财务审批',
                'node_type' => 'approval',
                'approval_type' => 'role',
                'approver_config' => ['role_id' => 1],
                'sequence' => 2,
                'is_required' => true,
            ]);

            $purchaseOrder = PurchaseOrder::first();
            if ($purchaseOrder) {
                $instance = WorkflowInstance::create([
                    'workflow_id' => $workflow->id,
                    'instance_no' => 'WF' . date('Ymd') . '001',
                    'reference_type' => 'App\Models\PurchaseOrder',
                    'reference_id' => $purchaseOrder->id,
                    'reference_no' => $purchaseOrder->order_no,
                    'status' => 'completed',
                    'started_by' => $admin->id,
                    'started_at' => now()->subDays(19),
                    'completed_at' => now()->subDays(17),
                ]);

                ApprovalRecord::create([
                    'instance_id' => $instance->id,
                    'node_id' => $workflow->nodes->first()->id,
                    'approver_id' => $manager->id,
                    'action' => 'approve',
                    'status' => 'approved',
                    'comment' => '同意',
                    'approved_at' => now()->subDays(18),
                ]);
            }
        }
    }
}

