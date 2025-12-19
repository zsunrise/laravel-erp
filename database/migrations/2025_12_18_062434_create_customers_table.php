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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('客户编码');
            $table->string('name')->comment('客户名称');
            $table->string('contact_person')->nullable()->comment('联系人');
            $table->string('contact_phone')->nullable()->comment('联系电话');
            $table->string('email')->nullable()->comment('邮箱');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->string('address')->nullable()->comment('地址');
            $table->string('tax_number')->nullable()->comment('税号');
            $table->string('bank_name')->nullable()->comment('开户银行');
            $table->string('bank_account')->nullable()->comment('银行账号');
            $table->string('rating')->default('C')->comment('评级：A/B/C/D');
            $table->decimal('credit_limit', 10, 2)->default(0)->comment('信用额度');
            $table->integer('payment_days')->default(0)->comment('账期天数');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
