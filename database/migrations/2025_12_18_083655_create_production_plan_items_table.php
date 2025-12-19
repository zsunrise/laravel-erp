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
        Schema::create('production_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained('production_plans')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('bom_id')->nullable()->constrained('boms')->onDelete('set null')->comment('使用的BOM');
            $table->foreignId('process_route_id')->nullable()->constrained('process_routes')->onDelete('set null')->comment('使用的工艺路线');
            $table->integer('planned_quantity')->comment('计划数量');
            $table->integer('completed_quantity')->default(0)->comment('已完成数量');
            $table->date('planned_start_date')->comment('计划开始日期');
            $table->date('planned_end_date')->comment('计划结束日期');
            $table->integer('priority')->default(0)->comment('优先级');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('production_plan_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_plan_items');
    }
};
