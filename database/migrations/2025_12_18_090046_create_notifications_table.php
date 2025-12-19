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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('接收用户ID');
            $table->string('type')->comment('消息类型：system-系统消息，approval-审批，order-订单，inventory-库存，financial-财务');
            $table->string('title')->comment('消息标题');
            $table->text('content')->comment('消息内容');
            $table->string('channel')->default('system')->comment('发送渠道：system-系统，email-邮件，sms-短信，push-推送');
            $table->string('status')->default('unread')->comment('状态：unread-未读，read-已读，deleted-已删除');
            $table->string('priority')->default('normal')->comment('优先级：low-低，normal-普通，high-高，urgent-紧急');
            $table->string('reference_type')->nullable()->comment('关联类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->json('data')->nullable()->comment('扩展数据');
            $table->timestamp('read_at')->nullable()->comment('阅读时间');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
