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
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('流程名称');
            $table->string('code')->unique()->comment('流程编码');
            $table->string('type')->comment('流程类型：purchase_order-采购订单，sales_order-销售订单，expense-费用，work_order-工单，production_plan-生产计划');
            $table->text('description')->nullable()->comment('流程描述');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->integer('version')->default(1)->comment('版本号');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index('type');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflows');
    }
};
