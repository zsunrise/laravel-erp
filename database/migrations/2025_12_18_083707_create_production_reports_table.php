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
        Schema::create('production_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_no')->unique()->comment('报工单号');
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('restrict');
            $table->foreignId('work_order_item_id')->nullable()->constrained('work_order_items')->onDelete('set null');
            $table->date('report_date')->comment('报工日期');
            $table->integer('quantity')->comment('产量');
            $table->integer('qualified_quantity')->default(0)->comment('合格数量');
            $table->integer('defective_quantity')->default(0)->comment('不良数量');
            $table->decimal('work_hours', 10, 2)->default(0)->comment('工时');
            $table->decimal('overtime_hours', 10, 2)->default(0)->comment('加班工时');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null')->comment('报工人');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index(['work_order_id', 'report_date']);
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_reports');
    }
};
