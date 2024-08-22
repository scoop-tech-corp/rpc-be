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
        Schema::table('promotionBundleDetails', function (Blueprint $table) {
            $table->dropColumn('percentOrAmount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotionBundleDetails', function (Blueprint $table) {
            $table->string('percentOrAmount')->after('productOrService');
        });
    }
};
