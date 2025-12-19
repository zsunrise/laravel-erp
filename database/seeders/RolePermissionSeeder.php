<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // 创建权限
        $permissions = [
            // 系统管理
            ['name' => '用户管理', 'slug' => 'users.manage', 'group' => 'system'],
            ['name' => '角色管理', 'slug' => 'roles.manage', 'group' => 'system'],
            ['name' => '权限管理', 'slug' => 'permissions.manage', 'group' => 'system'],
            ['name' => '系统配置', 'slug' => 'system.config', 'group' => 'system'],
            
            // 基础数据
            ['name' => '商品管理', 'slug' => 'products.manage', 'group' => 'basic'],
            ['name' => '商品分类管理', 'slug' => 'product-categories.manage', 'group' => 'basic'],
            ['name' => '仓库管理', 'slug' => 'warehouses.manage', 'group' => 'basic'],
            ['name' => '供应商管理', 'slug' => 'suppliers.manage', 'group' => 'basic'],
            ['name' => '客户管理', 'slug' => 'customers.manage', 'group' => 'basic'],
            
            // 库存管理
            ['name' => '库存查询', 'slug' => 'inventory.view', 'group' => 'inventory'],
            ['name' => '库存入库', 'slug' => 'inventory.stock-in', 'group' => 'inventory'],
            ['name' => '库存出库', 'slug' => 'inventory.stock-out', 'group' => 'inventory'],
            ['name' => '库存调拨', 'slug' => 'inventory.transfer', 'group' => 'inventory'],
            ['name' => '库存调整', 'slug' => 'inventory.adjust', 'group' => 'inventory'],
            ['name' => '库存盘点', 'slug' => 'inventory.stocktake', 'group' => 'inventory'],
            
            // 采购管理
            ['name' => '采购订单管理', 'slug' => 'purchase-orders.manage', 'group' => 'purchase'],
            ['name' => '采购审核', 'slug' => 'purchase-orders.approve', 'group' => 'purchase'],
            ['name' => '采购入库', 'slug' => 'purchase-orders.receive', 'group' => 'purchase'],
            ['name' => '采购退货', 'slug' => 'purchase-returns.manage', 'group' => 'purchase'],
            ['name' => '采购结算', 'slug' => 'purchase-settlements.manage', 'group' => 'purchase'],
            
            // 销售管理
            ['name' => '销售订单管理', 'slug' => 'sales-orders.manage', 'group' => 'sales'],
            ['name' => '销售审核', 'slug' => 'sales-orders.approve', 'group' => 'sales'],
            ['name' => '销售出库', 'slug' => 'sales-orders.ship', 'group' => 'sales'],
            ['name' => '销售退货', 'slug' => 'sales-returns.manage', 'group' => 'sales'],
            ['name' => '销售结算', 'slug' => 'sales-settlements.manage', 'group' => 'sales'],
            
            // 生产管理
            ['name' => 'BOM管理', 'slug' => 'boms.manage', 'group' => 'production'],
            ['name' => '工艺路线管理', 'slug' => 'process-routes.manage', 'group' => 'production'],
            ['name' => '生产计划管理', 'slug' => 'production-plans.manage', 'group' => 'production'],
            ['name' => '工单管理', 'slug' => 'work-orders.manage', 'group' => 'production'],
            ['name' => '生产报工', 'slug' => 'production-reports.manage', 'group' => 'production'],
            
            // 财务管理
            ['name' => '会计科目管理', 'slug' => 'chart-of-accounts.manage', 'group' => 'financial'],
            ['name' => '凭证管理', 'slug' => 'accounting-vouchers.manage', 'group' => 'financial'],
            ['name' => '凭证过账', 'slug' => 'accounting-vouchers.post', 'group' => 'financial'],
            ['name' => '总账查询', 'slug' => 'general-ledger.view', 'group' => 'financial'],
            ['name' => '应收管理', 'slug' => 'accounts-receivable.manage', 'group' => 'financial'],
            ['name' => '应付管理', 'slug' => 'accounts-payable.manage', 'group' => 'financial'],
            ['name' => '财务报表', 'slug' => 'financial-reports.view', 'group' => 'financial'],
            
            // 审批流程
            ['name' => '流程设计', 'slug' => 'workflows.manage', 'group' => 'approval'],
            ['name' => '审批处理', 'slug' => 'approvals.handle', 'group' => 'approval'],
            
            // 报表分析
            ['name' => '销售报表', 'slug' => 'sales-reports.view', 'group' => 'reports'],
            ['name' => '采购报表', 'slug' => 'purchase-reports.view', 'group' => 'reports'],
            ['name' => '库存报表', 'slug' => 'inventory-reports.view', 'group' => 'reports'],
            ['name' => '自定义报表', 'slug' => 'custom-reports.manage', 'group' => 'reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // 创建角色
        $adminRole = Role::create([
            'name' => '系统管理员',
            'slug' => 'admin',
            'description' => '拥有所有权限',
            'is_active' => true,
        ]);

        $managerRole = Role::create([
            'name' => '经理',
            'slug' => 'manager',
            'description' => '拥有业务管理权限',
            'is_active' => true,
        ]);

        $operatorRole = Role::create([
            'name' => '操作员',
            'slug' => 'operator',
            'description' => '拥有基础操作权限',
            'is_active' => true,
        ]);

        // 分配权限
        $adminRole->permissions()->sync(Permission::pluck('id'));
        
        $managerPermissions = Permission::whereIn('slug', [
            'products.manage', 'product-categories.manage', 'warehouses.manage',
            'suppliers.manage', 'customers.manage',
            'inventory.view', 'inventory.stock-in', 'inventory.stock-out',
            'purchase-orders.manage', 'purchase-orders.approve',
            'sales-orders.manage', 'sales-orders.approve',
            'production-plans.manage', 'work-orders.manage',
            'financial-reports.view',
            'sales-reports.view', 'purchase-reports.view', 'inventory-reports.view',
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPermissions);

        $operatorPermissions = Permission::whereIn('slug', [
            'inventory.view', 'inventory.stock-in', 'inventory.stock-out',
            'work-orders.manage', 'production-reports.manage',
        ])->pluck('id');
        $operatorRole->permissions()->sync($operatorPermissions);
    }
}
