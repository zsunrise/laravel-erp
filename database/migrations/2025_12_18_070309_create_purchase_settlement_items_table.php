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
        Schema::create('purchase_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_settlement_id')->constrained('purchase_settlements')->onDelete('cascade');
            $table->string('reference_type')->comment('关联类型：purchase_order, purchase_return');
            $table->unsignedBigInteger('reference_id')->comment('关联ID');
            $table->string('reference_no')->comment('关联单号');
            $table->decimal('amount', 10, 2)->comment('金额');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('purchase_settlement_id');
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
        Schema::dropIfExists('purchase_settlement_items');
    }
};
