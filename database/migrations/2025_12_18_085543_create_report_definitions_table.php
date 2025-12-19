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
        Schema::create('report_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('报表名称');
            $table->string('code')->unique()->comment('报表编码');
            $table->string('category')->comment('报表类别：sales-销售，purchase-采购，inventory-库存，financial-财务，custom-自定义');
            $table->text('description')->nullable()->comment('报表描述');
            $table->json('query_config')->comment('查询配置：表名、字段、条件、排序等');
            $table->json('display_config')->comment('显示配置：列定义、分组、汇总等');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->index('category');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_definitions');
    }
};
