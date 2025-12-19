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
        Schema::create('sales_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_settlement_id')->constrained('sales_settlements')->onDelete('cascade');
            $table->string('reference_type')->comment('关联类型：sales_order, sales_return');
            $table->unsignedBigInteger('reference_id')->comment('关联ID');
            $table->string('reference_no')->comment('关联单号');
            $table->decimal('amount', 10, 2)->comment('金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('sales_settlement_id');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_settlement_items');
    }
};
