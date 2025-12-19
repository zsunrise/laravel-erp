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
        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->onDelete('restrict');
            $table->string('instance_no')->unique()->comment('实例编号');
            $table->string('reference_type')->comment('关联类型：PurchaseOrder, SalesOrder, WorkOrder等');
            $table->unsignedBigInteger('reference_id')->comment('关联ID');
            $table->string('reference_no')->nullable()->comment('关联单号');
            $table->string('status')->default('pending')->comment('状态：pending-待审批，approved-已通过，rejected-已拒绝，cancelled-已取消');
            $table->foreignId('current_node_id')->nullable()->constrained('workflow_nodes')->onDelete('set null')->comment('当前节点ID');
            $table->foreignId('started_by')->nullable()->constrained('users')->onDelete('set null')->comment('发起人');
            $table->timestamp('started_at')->nullable()->comment('发起时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
            $table->index('workflow_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflow_instances');
    }
};
