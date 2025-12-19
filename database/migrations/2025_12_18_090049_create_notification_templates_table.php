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
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('模板编码');
            $table->string('name')->comment('模板名称');
            $table->string('type')->comment('消息类型：system-系统消息，approval-审批，order-订单，inventory-库存，financial-财务');
            $table->string('channel')->comment('发送渠道：system-系统，email-邮件，sms-短信，push-推送');
            $table->string('subject')->nullable()->comment('主题（邮件/短信）');
            $table->text('content')->comment('模板内容（支持变量：{variable}）');
            $table->json('variables')->nullable()->comment('可用变量说明');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'channel']);
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
        Schema::dropIfExists('notification_templates');
    }
};
