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
        Schema::create('production_material_issues', function (Blueprint $table) {
            $table->id();
            $table->string('issue_no')->unique()->comment('领料单号');
            $table->foreignId('work_order_id')->constrained('work_orders')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->date('issue_date')->comment('领料日期');
            $table->string('type')->default('issue')->comment('类型：issue-领料，return-退料');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，approved-已审核，completed-已完成，cancelled-已取消');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['work_order_id', 'type']);
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
        Schema::dropIfExists('production_material_issues');
    }
};
