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
        Schema::table('usersLocation', function (Blueprint $table) {
            $table->boolean('isMainLocation')->default(false)->after('locationId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usersLocation', function (Blueprint $table) {
            $table->dropColumn('isMainLocation');
        });
    }
};
