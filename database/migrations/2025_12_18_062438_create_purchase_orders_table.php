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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique()->comment('采购单号');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->date('order_date')->comment('订单日期');
            $table->date('expected_date')->nullable()->comment('预计到货日期');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，pending-待审核，approved-已审核，partial-部分入库，completed-已完成，cancelled-已取消');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('小计');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('税额');
            $table->decimal('discount_amount', 10, 2)->default(0)->comment('折扣金额');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('总金额');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['supplier_id', 'order_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
};
