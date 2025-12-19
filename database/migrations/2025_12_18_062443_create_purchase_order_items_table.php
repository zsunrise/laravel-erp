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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity')->comment('数量');
            $table->integer('received_quantity')->default(0)->comment('已收货数量');
            $table->decimal('unit_price', 10, 2)->comment('单价');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('税率');
            $table->decimal('discount_rate', 5, 2)->default(0)->comment('折扣率');
            $table->decimal('subtotal', 10, 2)->comment('小计');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('税额');
            $table->decimal('total_amount', 10, 2)->comment('总金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('purchase_order_id');
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
        Schema::dropIfExists('purchase_order_items');
    }
};
