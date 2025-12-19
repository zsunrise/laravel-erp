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
        Schema::create('cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('allocation_no')->unique()->comment('分配单号');
            $table->date('allocation_date')->comment('分配日期');
            $table->string('cost_type')->comment('成本类型：material-材料成本，labor-人工成本，overhead-制造费用');
            $table->string('reference_type')->nullable()->comment('关联类型：WorkOrder, ProductionPlan');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->decimal('total_amount', 15, 2)->comment('总金额');
            $table->decimal('allocated_amount', 15, 2)->default(0)->comment('已分配金额');
            $table->string('allocation_method')->default('direct')->comment('分配方法：direct-直接分配，proportion-按比例，quantity-按数量');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，approved-已审核，completed-已完成');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['allocation_date', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('cost_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_allocations');
    }
};
