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
        Schema::create('process_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->comment('产品ID');
            $table->string('version')->default('1.0')->comment('工艺路线版本');
            $table->date('effective_date')->comment('生效日期');
            $table->date('expiry_date')->nullable()->comment('失效日期');
            $table->boolean('is_default')->default(false)->comment('是否默认版本');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('描述');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'version']);
            $table->index('product_id');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('process_routes');
    }
};
