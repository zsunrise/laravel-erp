<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 将会计凭证状态字段从 string 改为 integer
     */
    public function up()
    {
        if (!Schema::hasTable('accounting_vouchers')) {
            return;
        }

        // 先添加临时列
        Schema::table('accounting_vouchers', function (Blueprint $table) {
            $table->integer('status_temp')->nullable()->after('status');
        });

        // 状态映射：旧字符串状态 => 新整数状态
        $statusMap = [
            'draft' => 1,      // 草稿
            'pending' => 2,     // 待审核
            'under_review' => 3, // 审核中
            'approved' => 4,    // 已审核
            'rejected' => 5,    // 已拒绝
            'posted' => 6,      // 已过账
            'cancelled' => 7,   // 已取消
        ];

        // 转换数据
        foreach ($statusMap as $stringStatus => $intStatus) {
            DB::table('accounting_vouchers')
                ->where('status', $stringStatus)
                ->update(['status_temp' => $intStatus]);
        }

        // 将未映射的值设置为默认值（草稿状态）
        DB::table('accounting_vouchers')
            ->whereNull('status_temp')
            ->update(['status_temp' => 1]);

        // 删除原列，重命名新列（使用原生 SQL）
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            // MySQL: 先删除原列，再重命名新列并设置注释
            DB::statement("ALTER TABLE `accounting_vouchers` DROP COLUMN `status`");
            DB::statement("ALTER TABLE `accounting_vouchers` CHANGE `status_temp` `status` INTEGER NOT NULL DEFAULT 1 COMMENT '状态：1-草稿，2-待审核，3-审核中，4-已审核，5-已拒绝，6-已过账，7-已取消'");
        } else {
            // PostgreSQL/SQLite: 使用 RENAME COLUMN
            DB::statement("ALTER TABLE accounting_vouchers DROP COLUMN status");
            DB::statement("ALTER TABLE accounting_vouchers RENAME COLUMN status_temp TO status");
            DB::statement("ALTER TABLE accounting_vouchers ALTER COLUMN status SET DEFAULT 1");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (!Schema::hasTable('accounting_vouchers')) {
            return;
        }

        // 回滚时将状态字段改回 string（使用原生 SQL）
        // 注意：回滚会丢失数据，因为无法准确还原字符串值
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `accounting_vouchers` MODIFY COLUMN `status` VARCHAR(255) NOT NULL DEFAULT 'draft'");
        } else {
            DB::statement("ALTER TABLE accounting_vouchers ALTER COLUMN status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE accounting_vouchers ALTER COLUMN status SET DEFAULT 'draft'");
        }
    }
};
