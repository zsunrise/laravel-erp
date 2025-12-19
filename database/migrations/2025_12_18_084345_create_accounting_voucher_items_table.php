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
        Schema::create('accounting_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('accounting_vouchers')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->string('direction')->comment('方向：debit-借方，credit-贷方');
            $table->decimal('amount', 15, 2)->comment('金额');
            $table->text('summary')->nullable()->comment('摘要');
            $table->string('reference_type')->nullable()->comment('关联类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->integer('sequence')->default(0)->comment('排序');
            $table->timestamps();
            $table->index('voucher_id');
            $table->index('account_id');
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
        Schema::dropIfExists('accounting_voucher_items');
    }
};
