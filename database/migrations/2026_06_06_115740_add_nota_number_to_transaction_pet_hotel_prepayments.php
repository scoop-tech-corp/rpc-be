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
        Schema::table('transaction_pet_hotel_prepayments', function (Blueprint $table) {
            $table->string('nota_number')->nullable()->after('transactionId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pet_hotel_prepayments', function (Blueprint $table) {
            $table->dropColumn('nota_number');
        });
    }
};
