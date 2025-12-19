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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('cascade');
            $table->foreignId('process_route_step_id')->nullable()->constrained('process_route_steps')->onDelete('set null')->comment('对应工序');
            $table->string('step_name')->comment('工序名称');
            $table->integer('sequence')->comment('工序顺序');
            $table->string('status')->default('pending')->comment('状态：pending-待开始，in_progress-进行中，completed-已完成');
            $table->decimal('planned_time', 10, 2)->default(0)->comment('计划工时');
            $table->decimal('actual_time', 10, 2)->default(0)->comment('实际工时');
            $table->date('planned_start_date')->nullable()->comment('计划开始日期');
            $table->date('planned_end_date')->nullable()->comment('计划结束日期');
            $table->date('actual_start_date')->nullable()->comment('实际开始日期');
            $table->date('actual_end_date')->nullable()->comment('实际结束日期');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null')->comment('派工给');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('work_order_id');
            $table->index(['work_order_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('work_order_items');
    }
};
