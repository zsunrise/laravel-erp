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
        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->onDelete('cascade');
            $table->foreignId('sales_order_item_id')->nullable()->constrained('sales_order_items')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('quantity')->comment('退货数量');
            $table->decimal('unit_price', 10, 2)->comment('单价');
            $table->decimal('tax_rate', 5, 2)->default(0)->comment('税率');
            $table->decimal('subtotal', 10, 2)->comment('小计');
            $table->decimal('tax_amount', 10, 2)->default(0)->comment('税额');
            $table->decimal('total_amount', 10, 2)->comment('总金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('sales_return_id');
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
        Schema::dropIfExists('sales_return_items');
    }
};
