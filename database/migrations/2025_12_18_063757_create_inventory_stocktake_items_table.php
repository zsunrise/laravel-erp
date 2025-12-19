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
        Schema::create('inventory_stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained('inventory_stocktakes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->integer('book_quantity')->default(0)->comment('账面数量');
            $table->integer('actual_quantity')->default(0)->comment('实际数量');
            $table->integer('difference_quantity')->default(0)->comment('差异数量');
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('单位成本');
            $table->decimal('difference_amount', 10, 2)->default(0)->comment('差异金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('stocktake_id');
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
        Schema::dropIfExists('inventory_stocktake_items');
    }
};
