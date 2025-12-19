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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_no')->unique()->comment('工单号');
            $table->foreignId('production_plan_id')->nullable()->constrained('production_plans')->onDelete('set null');
            $table->foreignId('production_plan_item_id')->nullable()->constrained('production_plan_items')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('bom_id')->nullable()->constrained('boms')->onDelete('set null');
            $table->foreignId('process_route_id')->nullable()->constrained('process_routes')->onDelete('set null');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->integer('quantity')->comment('生产数量');
            $table->integer('completed_quantity')->default(0)->comment('已完成数量');
            $table->date('start_date')->comment('开始日期');
            $table->date('planned_end_date')->comment('计划结束日期');
            $table->date('actual_end_date')->nullable()->comment('实际结束日期');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，approved-已审核，material_issued-已领料，in_progress-进行中，completed-已完成，cancelled-已取消');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->comment('派工给');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'status']);
            $table->index('warehouse_id');
            $table->index('start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_orders');
    }
};
