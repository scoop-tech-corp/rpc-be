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
        // Schema::create('table_access_control', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        // });

        Schema::dropIfExists('tableAccess');

        Schema::create('accessControl', function (Blueprint $table) {
            $table->id();
            $table->integer('menuListId');
            $table->integer('roleId');
            $table->integer('accessTypeId');
            $table->integer('accessLimitId');
            $table->integer('isDeleted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accessControl');

        Schema::create('tableAccess', function (Blueprint $table) {
            $table->id();
            $table->integer('menuListId');
            $table->integer('roleId');
            $table->integer('accessTypeId');
            $table->integer('accessLimitId');
            $table->timestamps();
        });
    }
};
