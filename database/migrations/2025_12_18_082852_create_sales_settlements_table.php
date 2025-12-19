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
        Schema::create('sales_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_no')->unique()->comment('结算单号');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->date('settlement_date')->comment('结算日期');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，pending-待审核，approved-已审核，paid-已收款，cancelled-已取消');
            $table->decimal('total_amount', 10, 2)->default(0)->comment('结算总金额');
            $table->decimal('received_amount', 10, 2)->default(0)->comment('已收金额');
            $table->decimal('remaining_amount', 10, 2)->default(0)->comment('剩余金额');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable()->comment('审核时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'settlement_date']);
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
        Schema::dropIfExists('sales_settlements');
    }
};
