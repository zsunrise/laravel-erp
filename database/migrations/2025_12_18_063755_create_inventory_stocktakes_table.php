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
        Schema::create('inventory_stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('stocktake_no')->unique()->comment('盘点单号');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->date('stocktake_date')->comment('盘点日期');
            $table->string('status')->default('draft')->comment('状态：draft-草稿，counting-盘点中，completed-已完成，cancelled-已取消');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['warehouse_id', 'stocktake_date']);
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
        Schema::dropIfExists('inventory_stocktakes');
    }
};
