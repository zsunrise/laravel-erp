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
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->onDelete('cascade');
            $table->foreignId('component_product_id')->constrained('products')->onDelete('restrict')->comment('子件产品ID');
            $table->decimal('quantity', 10, 4)->comment('用量');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('set null')->comment('单位');
            $table->decimal('loss_rate', 5, 2)->default(0)->comment('损耗率(%)');
            $table->integer('sequence')->default(0)->comment('排序');
            $table->string('position')->nullable()->comment('位置/工位');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('bom_id');
            $table->index('component_product_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bom_items');
    }
};
