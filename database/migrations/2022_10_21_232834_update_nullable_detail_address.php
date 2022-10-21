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
        Schema::table('location_detail_address', function (Blueprint $table) {
            $table->dropColumn('additionalInfo'); // Remove "active" field
            $table->dropColumn('addressName');
            $table->dropColumn('provinceCode');
            $table->dropColumn('cityCode');
            $table->dropColumn('postalCode');
            $table->dropColumn('country');
         });



         Schema::table('location_detail_address', function (Blueprint $table) {
            $table->string('addressName')->nullable();
            $table->string('additionalInfo')->nullable();
            $table->integer('provinceCode')->nullable();
            $table->integer('cityCode')->nullable();
            $table->integer('postalCode')->nullable();
            $table->string('country')->nullable();
         });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('location_detail_address', function (Blueprint $table) {
            $table->dropColumn('additionalInfo'); // Remove "active" field
            $table->dropColumn('addressName');
            $table->dropColumn('provinceCode');
            $table->dropColumn('cityCode');
            $table->dropColumn('postalCode');
            $table->dropColumn('country');
         });



         Schema::table('location_detail_address', function (Blueprint $table) {
            $table->string('addressName')->nullable();
            $table->string('additionalInfo')->nullable();
            $table->integer('provinceCode')->nullable();
            $table->integer('cityCode')->nullable();
            $table->integer('postalCode')->nullable();
            $table->string('country')->nullable();
         });
    }
};
