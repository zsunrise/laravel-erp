<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 将状态字段从 string 改为 integer
     */
    public function up()
    {
        // 采购订单状态转换
        $this->convertOrderStatus('purchase_orders', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'partial' => 4,
            'completed' => 5,
            'cancelled' => 6,
        ]);

        // 销售订单状态转换
        $this->convertOrderStatus('sales_orders', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'partial' => 4,
            'completed' => 5,
            'cancelled' => 6,
        ]);

        // 工作流实例状态转换
        $this->convertStatus('workflow_instances', [
            'pending' => 1,
            'approved' => 2,
            'rejected' => 3,
            'cancelled' => 4,
        ]);

        // 审批记录状态转换
        $this->convertStatus('approval_records', [
            'pending' => 1,
            'approved' => 2,
            'rejected' => 3,
            'transferred' => 4,
            'withdrawn' => 5,
        ]);

        // 工单状态转换
        $this->convertStatus('work_orders', [
            'draft' => 1,
            'approved' => 2,
            'material_issued' => 3,
            'in_progress' => 4,
            'completed' => 5,
            'cancelled' => 6,
        ]);

        // 生产计划状态转换
        $this->convertStatus('production_plans', [
            'draft' => 1,
            'approved' => 2,
            'in_progress' => 3,
            'completed' => 4,
            'cancelled' => 5,
        ]);

        // 采购退货单状态转换
        $this->convertOrderStatus('purchase_returns', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'completed' => 5,
            'cancelled' => 6,
        ]);

        // 销售退货单状态转换
        $this->convertOrderStatus('sales_returns', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'completed' => 5,
            'cancelled' => 6,
        ]);

        // 采购结算单状态转换
        $this->convertSettlementStatus('purchase_settlements', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'paid' => 4,
            'cancelled' => 6,
        ]);

        // 销售结算单状态转换
        $this->convertSettlementStatus('sales_settlements', [
            'draft' => 1,
            'pending' => 2,
            'approved' => 3,
            'paid' => 4,
            'cancelled' => 6,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // 回滚时将状态字段改回 string（使用原生 SQL）
        // 注意：回滚会丢失数据，因为无法准确还原字符串值
        $tables = [
            'purchase_orders', 'sales_orders', 'workflow_instances', 'approval_records',
            'work_orders', 'production_plans', 'purchase_returns', 'sales_returns',
            'purchase_settlements', 'sales_settlements'
        ];

        $driver = DB::getDriverName();
        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'status')) {
                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'draft'");
                } else {
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN status TYPE VARCHAR(255)");
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN status SET DEFAULT 'draft'");
                }
            }
        }
    }

    /**
     * 转换订单状态（通用订单状态）
     */
    protected function convertOrderStatus($table, $statusMap)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // 先添加临时列
        Schema::table($table, function (Blueprint $table) {
            $table->integer('status_temp')->nullable()->after('status');
        });

        // 转换数据
        foreach ($statusMap as $stringStatus => $intStatus) {
            DB::table($table)
                ->where('status', $stringStatus)
                ->update(['status_temp' => $intStatus]);
        }

        // 将未映射的 NULL 值设置为默认值（草稿状态）
        DB::table($table)
            ->whereNull('status_temp')
            ->update(['status_temp' => 1]);

        // 删除原列，重命名新列（使用原生 SQL）
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // MySQL: 先删除原列，再重命名新列并设置注释
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `status`");
            DB::statement("ALTER TABLE `{$table}` CHANGE `status_temp` `status` INTEGER NOT NULL DEFAULT 1 COMMENT '状态：1-草稿，2-待审核，3-已审核，4-部分入库/出库，5-已完成，6-已取消'");
        } else {
            // PostgreSQL/SQLite: 使用 RENAME COLUMN
            DB::statement("ALTER TABLE {$table} DROP COLUMN status");
            DB::statement("ALTER TABLE {$table} RENAME COLUMN status_temp TO status");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN status SET DEFAULT 1");
        }
    }

    /**
     * 转换通用状态
     */
    protected function convertStatus($table, $statusMap)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // 先添加临时列
        Schema::table($table, function (Blueprint $table) {
            $table->integer('status_temp')->nullable()->after('status');
        });

        // 转换数据
        foreach ($statusMap as $stringStatus => $intStatus) {
            DB::table($table)
                ->where('status', $stringStatus)
                ->update(['status_temp' => $intStatus]);
        }

        // 将未映射的 NULL 值设置为默认值（第一个状态值，通常是草稿）
        $defaultStatus = reset($statusMap); // 获取第一个状态值作为默认值
        DB::table($table)
            ->whereNull('status_temp')
            ->update(['status_temp' => $defaultStatus]);

        // 删除原列，重命名新列（使用原生 SQL）
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // MySQL: 先删除原列，再重命名新列
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `status`");
            DB::statement("ALTER TABLE `{$table}` CHANGE `status_temp` `status` INTEGER NOT NULL DEFAULT {$defaultStatus}");
        } else {
            // PostgreSQL/SQLite: 使用 RENAME COLUMN
            DB::statement("ALTER TABLE {$table} DROP COLUMN status");
            DB::statement("ALTER TABLE {$table} RENAME COLUMN status_temp TO status");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN status SET DEFAULT {$defaultStatus}");
        }
    }

    /**
     * 转换结算单状态
     */
    protected function convertSettlementStatus($table, $statusMap)
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        // 先添加临时列
        Schema::table($table, function (Blueprint $table) {
            $table->integer('status_temp')->nullable()->after('status');
        });

        // 转换数据
        foreach ($statusMap as $stringStatus => $intStatus) {
            DB::table($table)
                ->where('status', $stringStatus)
                ->update(['status_temp' => $intStatus]);
        }

        // 将未映射的 NULL 值设置为默认值（第一个状态值，通常是草稿）
        $defaultStatus = reset($statusMap); // 获取第一个状态值作为默认值
        DB::table($table)
            ->whereNull('status_temp')
            ->update(['status_temp' => $defaultStatus]);

        // 删除原列，重命名新列（使用原生 SQL）
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // MySQL: 先删除原列，再重命名新列
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `status`");
            DB::statement("ALTER TABLE `{$table}` CHANGE `status_temp` `status` INTEGER NOT NULL DEFAULT {$defaultStatus}");
        } else {
            // PostgreSQL/SQLite: 使用 RENAME COLUMN
            DB::statement("ALTER TABLE {$table} DROP COLUMN status");
            DB::statement("ALTER TABLE {$table} RENAME COLUMN status_temp TO status");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN status SET DEFAULT {$defaultStatus}");
        }
    }
};
