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
        if (Schema::hasTable('accounts_payable')) {
            Schema::rename('accounts_payable', 'accounts_payables');
        }
        if (Schema::hasTable('accounts_receivable')) {
            Schema::rename('accounts_receivable', 'accounts_receivables');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('accounts_payables')) {
            Schema::rename('accounts_payables', 'accounts_payable');
        }
        if (Schema::hasTable('accounts_receivables')) {
            Schema::rename('accounts_receivables', 'accounts_receivable');
        }
    }
};
