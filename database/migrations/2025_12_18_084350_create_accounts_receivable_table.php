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
        Schema::create('accounts_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->string('reference_type')->nullable()->comment('关联类型：SalesOrder, SalesSettlement');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->date('invoice_date')->comment('发票日期');
            $table->date('due_date')->comment('到期日期');
            $table->decimal('original_amount', 15, 2)->comment('原始金额');
            $table->decimal('received_amount', 15, 2)->default(0)->comment('已收金额');
            $table->decimal('remaining_amount', 15, 2)->comment('剩余金额');
            $table->integer('age_days')->default(0)->comment('账龄天数');
            $table->string('status')->default('outstanding')->comment('状态：outstanding-未结清，partial-部分结清，settled-已结清，overdue-逾期');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_receivables');
    }
};
