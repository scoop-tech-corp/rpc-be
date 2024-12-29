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
        Schema::table('customer', function (Blueprint $table) {
            $table->integer('userUpdateId')->after('createdBy');
        });

        Schema::table('customerPets', function (Blueprint $table) {
            $table->integer('createdBy')->after('deletedAt');
            $table->integer('userUpdateId')->after('createdBy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->dropColumn('userUpdateId');
        });

        Schema::table('customerPets', function (Blueprint $table) {
            $table->dropColumn('createdBy');
            $table->dropColumn('userUpdateId');
        });
    }
};
