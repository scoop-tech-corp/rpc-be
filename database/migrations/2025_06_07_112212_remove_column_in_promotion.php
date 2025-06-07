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
        Schema::table('promotionFreeItems', function (Blueprint $table) {
            $table->dropColumn('productBuyType');
            $table->dropColumn('productFreeType');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotionFreeItems', function (Blueprint $table) {
            $table->string('productBuyType')->after('quantityBuyItem');
            $table->string('productFreeType')->after('quantityFreeItem');
        });
    }
};
