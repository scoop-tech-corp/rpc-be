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
        Schema::table('menuMaster', function (Blueprint $table) {

            $table->integer('userId')->after('created_at');
            $table->integer('userUpdateId')->nullable()->after('updated_at');
            $table->string('deletedBy')->nullable()->after('userUpdateId');
            $table->timestamp('deletedAt', 0)->nullable()->after('deletedBy');
        });

        Schema::table('menuList', function (Blueprint $table) {

            $table->integer('userId')->after('created_at');
            $table->integer('userUpdateId')->nullable()->after('updated_at');
            $table->string('deletedBy')->nullable()->after('userUpdateId');
            $table->timestamp('deletedAt', 0)->nullable()->after('deletedBy');
        });

        DB::statement('UPDATE menuMaster SET userId = 1 WHERE userId = 0;');
        DB::statement('UPDATE menuList SET userId = 1 WHERE userId = 0;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menuMaster', function (Blueprint $table) {
            $table->dropColumn('userId');
            $table->dropColumn('userUpdateId');
            $table->dropColumn('deletedBy');
            $table->dropColumn('deletedAt');
        });

        Schema::table('menuList', function (Blueprint $table) {
            $table->dropColumn('userId');
            $table->dropColumn('userUpdateId');
            $table->dropColumn('deletedBy');
            $table->dropColumn('deletedAt');
        });
    }
};
