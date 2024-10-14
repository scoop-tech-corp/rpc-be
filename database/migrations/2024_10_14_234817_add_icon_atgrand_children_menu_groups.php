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
        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->string('icon')->after('url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->dropColumn('icon')->after('url');
        });
    }
};
