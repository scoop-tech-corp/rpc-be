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
        Schema::table('productSellBatches', function (Blueprint $table) {
            $table->integer('productTransferDetailId')->after('productTransferId');
        });

        Schema::table('productClinicBatches', function (Blueprint $table) {
            $table->integer('productTransferDetailId')->after('productTransferId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productSellBatches', function (Blueprint $table) {
            $table->dropColumn('productTransferDetailId');
        });

        Schema::table('productClinicBatches', function (Blueprint $table) {
            $table->dropColumn('productTransferDetailId');
        });
    }
};
