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
        Schema::create('data_dictionaries', function (Blueprint $table) {
            $table->id();
            $table->string('type')->comment('字典类型');
            $table->string('code')->comment('字典编码');
            $table->string('label')->comment('字典标签');
            $table->string('value')->comment('字典值');
            $table->integer('sort')->default(0)->comment('排序');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('描述');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('data_dictionaries');
    }
};
