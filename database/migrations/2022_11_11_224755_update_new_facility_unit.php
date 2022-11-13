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
        
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->string('locationName')->after('facilityCode'); 
            $table->integer('capacity')->after('status'); 
            $table->integer('amount')->after('capacity'); 
            $table->dropColumn('facilityCode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->string('locationName'); 
            $table->integer('capacity'); 
            $table->integer('amount'); 
            $table->dropColumn('facilityCode');
        });
    }
};
