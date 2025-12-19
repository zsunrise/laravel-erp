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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->integer('quantity')->default(0)->comment('库存数量');
            $table->integer('available_quantity')->default(0)->comment('可用数量');
            $table->integer('reserved_quantity')->default(0)->comment('预留数量');
            $table->decimal('average_cost', 10, 2)->default(0)->comment('平均成本');
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id', 'location_id']);
            $table->index(['product_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventories');
    }
};
