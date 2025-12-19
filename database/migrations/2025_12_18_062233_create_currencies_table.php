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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('币种名称');
            $table->string('code', 3)->unique()->comment('币种代码');
            $table->string('symbol')->nullable()->comment('币种符号');
            $table->decimal('exchange_rate', 10, 4)->default(1)->comment('汇率');
            $table->boolean('is_default')->default(false)->comment('是否默认');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
};
