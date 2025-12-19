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
        Schema::create('process_route_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('process_route_id')->constrained('process_routes')->onDelete('cascade');
            $table->string('step_name')->comment('工序名称');
            $table->string('step_code')->nullable()->comment('工序编码');
            $table->integer('sequence')->comment('工序顺序');
            $table->string('work_center')->nullable()->comment('工作中心');
            $table->decimal('standard_time', 10, 2)->default(0)->comment('标准工时(小时)');
            $table->decimal('setup_time', 10, 2)->default(0)->comment('准备时间(小时)');
            $table->decimal('queue_time', 10, 2)->default(0)->comment('排队时间(小时)');
            $table->decimal('move_time', 10, 2)->default(0)->comment('移动时间(小时)');
            $table->text('description')->nullable()->comment('工序描述');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('process_route_id');
            $table->index(['process_route_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_route_steps');
    }
};
