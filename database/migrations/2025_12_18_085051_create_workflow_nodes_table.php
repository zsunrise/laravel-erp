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
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('cascade');
            $table->string('node_name')->comment('节点名称');
            $table->string('node_type')->comment('节点类型：start-开始，approval-审批，condition-条件，end-结束');
            $table->integer('sequence')->comment('节点顺序');
            $table->string('approval_type')->nullable()->comment('审批类型：single-单人审批，all-会签，any-或签');
            $table->json('approver_config')->nullable()->comment('审批人配置：user_ids-用户ID数组，role_ids-角色ID数组，dept_ids-部门ID数组');
            $table->json('condition_config')->nullable()->comment('条件配置：字段、操作符、值');
            $table->json('next_nodes')->nullable()->comment('下一节点ID数组');
            $table->integer('timeout_hours')->nullable()->comment('超时时间（小时）');
            $table->boolean('is_required')->default(true)->comment('是否必审');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index(['workflow_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflow_nodes');
    }
};
