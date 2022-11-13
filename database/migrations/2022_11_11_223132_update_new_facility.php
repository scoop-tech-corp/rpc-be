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
        Schema::table('facility', function (Blueprint $table) {
            $table->dropColumn('facilityName');
            $table->dropColumn('capacity');
            $table->dropColumn('status');
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
        Schema::table('facility', function (Blueprint $table) {
            $table->dropColumn('facilityName');
            $table->dropColumn('capacity');
            $table->dropColumn('status');
            $table->dropColumn('facilityCode');
        });

    }
};
