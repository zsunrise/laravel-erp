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
        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('配置键');
            $table->text('value')->nullable()->comment('配置值');
            $table->string('type')->default('string')->comment('配置类型');
            $table->string('group')->nullable()->comment('配置分组');
            $table->text('description')->nullable()->comment('配置描述');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_configs');
    }
};
