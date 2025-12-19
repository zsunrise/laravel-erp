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
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained('workflow_instances')->onDelete('cascade');
            $table->foreignId('node_id')->constrained('workflow_nodes')->onDelete('restrict');
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict')->comment('审批人');
            $table->string('action')->comment('操作：approve-通过，reject-拒绝，transfer-转交，withdraw-撤回');
            $table->string('status')->default('pending')->comment('状态：pending-待审批，approved-已通过，rejected-已拒绝，transferred-已转交，withdrawn-已撤回');
            $table->text('comment')->nullable()->comment('审批意见');
            $table->foreignId('transferred_to')->nullable()->constrained('users')->onDelete('set null')->comment('转交给');
            $table->timestamp('approved_at')->nullable()->comment('审批时间');
            $table->timestamps();
            $table->index(['instance_id', 'node_id']);
            $table->index('approver_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('approval_records');
    }
};
