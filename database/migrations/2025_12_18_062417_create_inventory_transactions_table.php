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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->string('type')->comment('类型：in-入库，out-出库，transfer-调拨，adjust-调整');
            $table->string('reference_type')->nullable()->comment('关联类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->integer('quantity')->comment('数量');
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('单位成本');
            $table->decimal('total_cost', 10, 2)->default(0)->comment('总成本');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamp('transaction_date')->comment('交易日期');
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
