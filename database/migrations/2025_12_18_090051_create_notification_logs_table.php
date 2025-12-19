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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->nullable()->constrained('notifications')->onDelete('set null');
            $table->foreignId('template_id')->nullable()->constrained('notification_templates')->onDelete('set null');
            $table->string('channel')->comment('发送渠道：system-系统，email-邮件，sms-短信，push-推送');
            $table->string('recipient_type')->comment('接收者类型：user-用户，email-邮箱，phone-手机号');
            $table->string('recipient')->comment('接收者（用户ID、邮箱或手机号）');
            $table->string('status')->default('pending')->comment('状态：pending-待发送，sent-已发送，failed-发送失败');
            $table->text('content')->comment('发送内容');
            $table->text('error_message')->nullable()->comment('错误信息');
            $table->timestamp('sent_at')->nullable()->comment('发送时间');
            $table->timestamps();
            $table->index(['channel', 'status']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_logs');
    }
};
