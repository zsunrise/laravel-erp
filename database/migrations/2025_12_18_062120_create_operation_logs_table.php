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
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('module')->nullable()->comment('操作模块');
            $table->string('action')->comment('操作动作');
            $table->string('method')->nullable()->comment('请求方法');
            $table->string('path')->nullable()->comment('请求路径');
            $table->text('request_data')->nullable()->comment('请求数据');
            $table->text('response_data')->nullable()->comment('响应数据');
            $table->string('ip')->nullable()->comment('IP地址');
            $table->string('user_agent')->nullable()->comment('用户代理');
            $table->integer('status_code')->nullable()->comment('状态码');
            $table->text('message')->nullable()->comment('操作说明');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index('module');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('operation_logs');
    }
};
