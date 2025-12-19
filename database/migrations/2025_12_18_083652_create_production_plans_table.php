<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('production_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_no')->unique()->comment('计划单号');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->onDelete('set null')->comment('关联销售订单');
            $table->date('plan_date')->comment('计划日期');
            $table->date('start_date')->comment('开始日期');
            $table->date('end_date')->comment('结束日期');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，approved-已审核，in_progress-进行中，completed-已完成，cancelled-已取消');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict')->comment('生产仓库');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['plan_date', 'status']);
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_plans');
    }
};
