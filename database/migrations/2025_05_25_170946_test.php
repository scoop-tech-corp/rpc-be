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

            $table->integer('totalUsePromo')->default(false)->after('totalPayment');
            $table->integer('totalItem')->default(false)->after('totalUsePromo');
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
            $table->dropColumn('totalUsePromo');
            $table->dropColumn('totalItem');
        });
    }
};
