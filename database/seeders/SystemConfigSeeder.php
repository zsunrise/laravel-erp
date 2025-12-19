<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class SystemConfigSeeder extends Seeder
{
    public function run()
    {
        $configs = [
            // 系统基础配置
            ['key' => 'system.name', 'value' => 'ERP管理系统', 'type' => 'string', 'group' => 'system', 'description' => '系统名称'],
            ['key' => 'system.version', 'value' => '1.0.0', 'type' => 'string', 'group' => 'system', 'description' => '系统版本'],
            ['key' => 'system.company_name', 'value' => '示例公司', 'type' => 'string', 'group' => 'system', 'description' => '公司名称'],
            
            // 库存配置
            ['key' => 'inventory.auto_cost_calculation', 'value' => '1', 'type' => 'boolean', 'group' => 'inventory', 'description' => '自动计算成本'],
            ['key' => 'inventory.cost_method', 'value' => 'average', 'type' => 'string', 'group' => 'inventory', 'description' => '成本计算方法：average-平均成本，fifo-先进先出，lifo-后进先出'],
            ['key' => 'inventory.negative_stock_allowed', 'value' => '0', 'type' => 'boolean', 'group' => 'inventory', 'description' => '是否允许负库存'],
            
            // 采购配置
            ['key' => 'purchase.default_payment_days', 'value' => '30', 'type' => 'number', 'group' => 'purchase', 'description' => '默认账期天数'],
            ['key' => 'purchase.auto_create_receipt', 'value' => '0', 'type' => 'boolean', 'group' => 'purchase', 'description' => '审核后自动创建入库单'],
            
            // 销售配置
            ['key' => 'sales.default_payment_days', 'value' => '30', 'type' => 'number', 'group' => 'sales', 'description' => '默认账期天数'],
            ['key' => 'sales.auto_create_shipment', 'value' => '0', 'type' => 'boolean', 'group' => 'sales', 'description' => '审核后自动创建出库单'],
            ['key' => 'sales.check_credit_limit', 'value' => '1', 'type' => 'boolean', 'group' => 'sales', 'description' => '是否检查信用额度'],
            
            // 生产配置
            ['key' => 'production.auto_create_work_order', 'value' => '0', 'type' => 'boolean', 'group' => 'production', 'description' => '审核生产计划后自动创建工单'],
            ['key' => 'production.auto_issue_material', 'value' => '0', 'type' => 'boolean', 'group' => 'production', 'description' => '工单审核后自动领料'],
            
            // 财务配置
            ['key' => 'financial.default_currency', 'value' => 'CNY', 'type' => 'string', 'group' => 'financial', 'description' => '默认币种'],
            ['key' => 'financial.tax_rate', 'value' => '0.13', 'type' => 'number', 'group' => 'financial', 'description' => '默认税率'],
            ['key' => 'financial.fiscal_year_start', 'value' => '01-01', 'type' => 'string', 'group' => 'financial', 'description' => '会计年度起始日期'],
            
            // 审批配置
            ['key' => 'approval.purchase_order_required', 'value' => '1', 'type' => 'boolean', 'group' => 'approval', 'description' => '采购订单是否需要审批'],
            ['key' => 'approval.sales_order_required', 'value' => '1', 'type' => 'boolean', 'group' => 'approval', 'description' => '销售订单是否需要审批'],
            ['key' => 'approval.production_plan_required', 'value' => '1', 'type' => 'boolean', 'group' => 'approval', 'description' => '生产计划是否需要审批'],
        ];

        foreach ($configs as $config) {
            SystemConfig::create($config);
        }
    }
}
