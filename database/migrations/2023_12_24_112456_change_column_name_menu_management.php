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
        Schema::table('menuGroups', function (Blueprint $table) {
            $table->renameColumn('orderData', 'orderMenu');
        });

        Schema::table('childrenMenuGroups', function (Blueprint $table) {
            $table->renameColumn('orderData', 'orderMenu');
        });

        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->renameColumn('orderData', 'orderMenu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menuGroups', function (Blueprint $table) {
            $table->renameColumn('orderMenu', 'orderData');
        });

        Schema::table('childrenMenuGroups', function (Blueprint $table) {
            $table->renameColumn('orderMenu', 'orderData');
        });

        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->renameColumn('orderMenu', 'orderData');
        });
    }
};
