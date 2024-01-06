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
        Schema::table('accessControl', function (Blueprint $table) {
            $table->dropColumn('accessLimitId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accessControl', function (Blueprint $table) {
            $table->integer('accessLimitId');
            // $table->integer('menuListId');
            // $table->integer('roleId');
            // $table->integer('accessTypeId');
            // $table->integer('accessLimitId');
            // $table->integer('isDeleted');
            // $table->timestamps();
        });
    }
};
