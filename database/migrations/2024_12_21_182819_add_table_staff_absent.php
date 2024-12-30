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
        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('shift')->after('id');
            $table->string('status')->after('shift');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('shift');
            $table->dropColumn('status');
        });
    }
};
