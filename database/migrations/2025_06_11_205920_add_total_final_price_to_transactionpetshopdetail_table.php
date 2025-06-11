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
        Schema::table('transactionpetshopdetail', function (Blueprint $table) {
            $table->integer('total_final_price')->nullable()->after('final_price');
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
            $table->dropColumn('total_final_price');
        });
    }
};
