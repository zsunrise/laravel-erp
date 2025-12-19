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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('仓库名称');
            $table->string('code')->unique()->comment('仓库编码');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
            $table->string('address')->nullable()->comment('仓库地址');
            $table->string('contact_person')->nullable()->comment('联系人');
            $table->string('contact_phone')->nullable()->comment('联系电话');
            $table->boolean('is_default')->default(false)->comment('是否默认仓库');
            $table->boolean('is_active')->default(true)->comment('是否启用');
            $table->text('description')->nullable()->comment('描述');
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
        Schema::dropIfExists('warehouses');
    }
};
