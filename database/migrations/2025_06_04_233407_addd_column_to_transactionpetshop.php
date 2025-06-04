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
        Schema::table('transactionpetshop', function (Blueprint $table) {
            $table->string('no_nota')->nullable()->after('registrationNo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactionpetshop', function (Blueprint $table) {
            $table->dropColumn('no_nota');
        });
    }
};
