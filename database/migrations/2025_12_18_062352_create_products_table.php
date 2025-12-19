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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->string('name')->comment('商品名称');
            $table->string('sku')->unique()->comment('SKU编码');
            $table->string('barcode')->nullable()->comment('条形码');
            $table->text('description')->nullable()->comment('商品描述');
            $table->string('image')->nullable()->comment('商品图片');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->decimal('purchase_price', 10, 2)->default(0)->comment('采购价');
            $table->decimal('sale_price', 10, 2)->default(0)->comment('销售价');
            $table->decimal('cost_price', 10, 2)->default(0)->comment('成本价');
            $table->integer('min_stock')->default(0)->comment('最低库存');
            $table->integer('max_stock')->default(0)->comment('最高库存');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
            $table->softDeletes();
            $table->index('category_id');
            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
