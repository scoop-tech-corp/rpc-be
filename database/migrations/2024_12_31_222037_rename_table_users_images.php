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
        Schema::rename('usersImages', 'usersIdentifications');

        Schema::table('usersIdentifications', function (Blueprint $table) {
            $table->integer('typeId')->after('usersId');
            $table->string('identification')->after('typeId');
        });

        // DB::statement("
        //     INSERT INTO usersIdentifications (usersId, typeId, identification)
        //     SELECT id, typeId, identification
        //     FROM users
        // ");

        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('typeId');
        //     $table->dropColumn('identification');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usersIdentifications', function (Blueprint $table) {
            $table->dropColumn('typeId');
            $table->dropColumn('identification');
        });

        Schema::rename('usersIdentifications', 'usersImages');
    }
};
