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
        Schema::create('accounting_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique()->comment('凭证号');
            $table->date('voucher_date')->comment('凭证日期');
            $table->string('type')->default('general')->comment('凭证类型：general-普通，adjustment-调整，closing-结账');
            $table->integer('attachment_count')->default(0)->comment('附件数量');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，posted-已过账，cancelled-已取消');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable()->comment('过账时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['voucher_date', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_vouchers');
    }
};
