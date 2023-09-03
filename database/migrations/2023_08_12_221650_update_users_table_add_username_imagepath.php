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
        Schema::table('users', function (Blueprint $table) {
            $table->string('userName')->nullable()->after('id');
            $table->string('imageName')->nullable()->after('roleId');
            $table->string('imagePath')->nullable()->after('imageName');
        });

        DB::statement('UPDATE users SET imageName = "" WHERE imageName IS NULL;');
        DB::statement('UPDATE users SET imagePath = "" WHERE imagePath IS NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('userName');
            $table->dropColumn('imageName');
            $table->dropColumn('imagePath');
        });
    }
};
