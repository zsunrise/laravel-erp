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
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->date('entry_date')->comment('入账日期');
            $table->foreignId('voucher_id')->nullable()->constrained('accounting_vouchers')->onDelete('set null');
            $table->string('voucher_no')->nullable()->comment('凭证号');
            $table->text('summary')->nullable()->comment('摘要');
            $table->decimal('debit_amount', 15, 2)->default(0)->comment('借方金额');
            $table->decimal('credit_amount', 15, 2)->default(0)->comment('贷方金额');
            $table->decimal('balance', 15, 2)->default(0)->comment('余额');
            $table->string('reference_type')->nullable()->comment('关联类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->timestamps();
            $table->index(['account_id', 'entry_date']);
            $table->index('voucher_id');
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
        Schema::dropIfExists('general_ledger');
    }
};
