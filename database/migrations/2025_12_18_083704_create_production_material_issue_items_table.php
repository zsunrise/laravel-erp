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
        Schema::create('production_material_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_issue_id')->constrained('production_material_issues')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('location_id')->nullable()->constrained('warehouse_locations')->onDelete('set null');
            $table->integer('quantity')->comment('数量');
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('单位成本');
            $table->decimal('total_cost', 10, 2)->default(0)->comment('总成本');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('material_issue_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('production_material_issue_items');
    }
};
