<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run()
    {
        // 资产类科目
        $assets = [
            ['code' => '1000', 'name' => '资产', 'type' => 'asset', 'category' => 'asset', 'level' => 1, 'order' => 1],
            ['code' => '1001', 'name' => '流动资产', 'type' => 'asset', 'category' => 'current_asset', 'level' => 2, 'order' => 1, 'parent_code' => '1000'],
            ['code' => '100101', 'name' => '库存现金', 'type' => 'asset', 'category' => 'current_asset', 'level' => 3, 'order' => 1, 'parent_code' => '1001', 'is_detail' => true],
            ['code' => '100102', 'name' => '银行存款', 'type' => 'asset', 'category' => 'current_asset', 'level' => 3, 'order' => 2, 'parent_code' => '1001', 'is_detail' => true],
            ['code' => '100103', 'name' => '应收账款', 'type' => 'asset', 'category' => 'current_asset', 'level' => 3, 'order' => 3, 'parent_code' => '1001', 'is_detail' => true],
            ['code' => '100104', 'name' => '预付账款', 'type' => 'asset', 'category' => 'current_asset', 'level' => 3, 'order' => 4, 'parent_code' => '1001', 'is_detail' => true],
            ['code' => '100105', 'name' => '存货', 'type' => 'asset', 'category' => 'current_asset', 'level' => 3, 'order' => 5, 'parent_code' => '1001', 'is_detail' => true],
            ['code' => '1002', 'name' => '固定资产', 'type' => 'asset', 'category' => 'fixed_asset', 'level' => 2, 'order' => 2, 'parent_code' => '1000'],
            ['code' => '100201', 'name' => '固定资产原值', 'type' => 'asset', 'category' => 'fixed_asset', 'level' => 3, 'order' => 1, 'parent_code' => '1002', 'is_detail' => true],
            ['code' => '100202', 'name' => '累计折旧', 'type' => 'asset', 'category' => 'fixed_asset', 'level' => 3, 'order' => 2, 'parent_code' => '1002', 'is_detail' => true],
        ];

        // 负债类科目
        $liabilities = [
            ['code' => '2000', 'name' => '负债', 'type' => 'liability', 'category' => 'liability', 'level' => 1, 'order' => 2],
            ['code' => '2001', 'name' => '流动负债', 'type' => 'liability', 'category' => 'current_liability', 'level' => 2, 'order' => 1, 'parent_code' => '2000'],
            ['code' => '200101', 'name' => '应付账款', 'type' => 'liability', 'category' => 'current_liability', 'level' => 3, 'order' => 1, 'parent_code' => '2001', 'is_detail' => true],
            ['code' => '200102', 'name' => '预收账款', 'type' => 'liability', 'category' => 'current_liability', 'level' => 3, 'order' => 2, 'parent_code' => '2001', 'is_detail' => true],
            ['code' => '200103', 'name' => '应付职工薪酬', 'type' => 'liability', 'category' => 'current_liability', 'level' => 3, 'order' => 3, 'parent_code' => '2001', 'is_detail' => true],
            ['code' => '200104', 'name' => '应交税费', 'type' => 'liability', 'category' => 'current_liability', 'level' => 3, 'order' => 4, 'parent_code' => '2001', 'is_detail' => true],
            ['code' => '2002', 'name' => '长期负债', 'type' => 'liability', 'category' => 'long_term_liability', 'level' => 2, 'order' => 2, 'parent_code' => '2000'],
            ['code' => '200201', 'name' => '长期借款', 'type' => 'liability', 'category' => 'long_term_liability', 'level' => 3, 'order' => 1, 'parent_code' => '2002', 'is_detail' => true],
        ];

        // 权益类科目
        $equity = [
            ['code' => '3000', 'name' => '所有者权益', 'type' => 'equity', 'category' => 'equity', 'level' => 1, 'order' => 3],
            ['code' => '3001', 'name' => '实收资本', 'type' => 'equity', 'category' => 'equity', 'level' => 2, 'order' => 1, 'parent_code' => '3000', 'is_detail' => true],
            ['code' => '3002', 'name' => '资本公积', 'type' => 'equity', 'category' => 'equity', 'level' => 2, 'order' => 2, 'parent_code' => '3000', 'is_detail' => true],
            ['code' => '3003', 'name' => '盈余公积', 'type' => 'equity', 'category' => 'equity', 'level' => 2, 'order' => 3, 'parent_code' => '3000', 'is_detail' => true],
            ['code' => '3004', 'name' => '未分配利润', 'type' => 'equity', 'category' => 'equity', 'level' => 2, 'order' => 4, 'parent_code' => '3000', 'is_detail' => true],
        ];

        // 收入类科目
        $revenue = [
            ['code' => '4000', 'name' => '收入', 'type' => 'revenue', 'category' => 'revenue', 'level' => 1, 'order' => 4],
            ['code' => '4001', 'name' => '主营业务收入', 'type' => 'revenue', 'category' => 'revenue', 'level' => 2, 'order' => 1, 'parent_code' => '4000', 'is_detail' => true],
            ['code' => '4002', 'name' => '其他业务收入', 'type' => 'revenue', 'category' => 'revenue', 'level' => 2, 'order' => 2, 'parent_code' => '4000', 'is_detail' => true],
            ['code' => '4003', 'name' => '营业外收入', 'type' => 'revenue', 'category' => 'revenue', 'level' => 2, 'order' => 3, 'parent_code' => '4000', 'is_detail' => true],
        ];

        // 费用类科目
        $expenses = [
            ['code' => '5000', 'name' => '成本费用', 'type' => 'expense', 'category' => 'expense', 'level' => 1, 'order' => 5],
            ['code' => '5001', 'name' => '主营业务成本', 'type' => 'expense', 'category' => 'cost', 'level' => 2, 'order' => 1, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5002', 'name' => '其他业务成本', 'type' => 'expense', 'category' => 'cost', 'level' => 2, 'order' => 2, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5003', 'name' => '销售费用', 'type' => 'expense', 'category' => 'expense', 'level' => 2, 'order' => 3, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5004', 'name' => '管理费用', 'type' => 'expense', 'category' => 'expense', 'level' => 2, 'order' => 4, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5005', 'name' => '财务费用', 'type' => 'expense', 'category' => 'expense', 'level' => 2, 'order' => 5, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5006', 'name' => '制造费用', 'type' => 'expense', 'category' => 'expense', 'level' => 2, 'order' => 6, 'parent_code' => '5000', 'is_detail' => true],
            ['code' => '5007', 'name' => '营业外支出', 'type' => 'expense', 'category' => 'expense', 'level' => 2, 'order' => 7, 'parent_code' => '5000', 'is_detail' => true],
        ];

        $allAccounts = array_merge($assets, $liabilities, $equity, $revenue, $expenses);

        foreach ($allAccounts as $account) {
            $parentId = null;
            if (isset($account['parent_code'])) {
                $parent = ChartOfAccount::where('code', $account['parent_code'])->first();
                if ($parent) {
                    $parentId = $parent->id;
                }
            }

            ChartOfAccount::create([
                'code' => $account['code'],
                'name' => $account['name'],
                'parent_id' => $parentId,
                'type' => $account['type'],
                'category' => $account['category'],
                'level' => $account['level'],
                'order' => $account['order'],
                'is_detail' => $account['is_detail'] ?? false,
                'is_active' => true,
            ]);
        }
    }
}
