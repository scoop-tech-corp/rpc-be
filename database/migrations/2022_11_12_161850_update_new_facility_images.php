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
        Schema::table('facility_images', function (Blueprint $table) {
            $table->string('locationName')->after('facilityCode'); 
            $table->string('unitName')->after('locationName'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facility_images', function (Blueprint $table) {
            $table->dropColumn('locationName'); 
            $table->dropColumn('unitName'); 
        });
    }
};
