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

        Schema::table('location_images', function (Blueprint $table) {
            $table->string('labelName')->after('codeLocation'); 
            $table->string('realImageName')->after('labelName'); 
         });


         Schema::table('facility_images', function (Blueprint $table) {
            $table->string('labelName')->after('facilityCode'); 
            $table->string('realImageName')->after('labelName'); 
         });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('location_images', function (Blueprint $table) {
            $table->string('labelName')->after('codeLocation'); 
            $table->string('realImageName')->after('labelName'); 
         });

         Schema::table('facility_images', function (Blueprint $table) {
            $table->string('labelName')->after('facilityCode'); 
            $table->string('realImageName')->after('labelName'); 
         });

    }
};
