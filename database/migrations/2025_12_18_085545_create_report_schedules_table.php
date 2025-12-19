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
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('report_definitions')->onDelete('cascade');
            $table->string('schedule_type')->comment('调度类型：daily-每天，weekly-每周，monthly-每月');
            $table->string('schedule_time')->comment('调度时间：HH:mm格式');
            $table->json('recipients')->nullable()->comment('接收人：用户ID数组或邮箱数组');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamp('last_run_at')->nullable()->comment('最后运行时间');
            $table->timestamp('next_run_at')->nullable()->comment('下次运行时间');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->index(['report_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_schedules');
    }
};
