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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('科目编码');
            $table->string('name')->comment('科目名称');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade')->comment('父科目ID');
            $table->string('type')->comment('科目类型：asset-资产，liability-负债，equity-权益，revenue-收入，expense-费用');
            $table->string('category')->comment('科目类别：current_asset-流动资产，fixed_asset-固定资产，current_liability-流动负债，long_term_liability-长期负债，equity-所有者权益，revenue-收入，cost-成本，expense-费用');
            $table->boolean('is_detail')->default(false)->comment('是否明细科目');
            $table->integer('level')->default(1)->comment('科目级别');
            $table->integer('order')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();
            $table->index('parent_id');
            $table->index('type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
