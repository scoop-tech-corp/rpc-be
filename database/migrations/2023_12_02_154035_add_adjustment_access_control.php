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
            $table->integer('orderData')->after('groupName');
        });

        Schema::table('childrenMenuGroups', function (Blueprint $table) {
            $table->integer('orderData')->after('groupId');
            $table->string('menuName')->after('orderData');
            $table->boolean('isActive')->after('icon');
        });

        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->integer('orderData')->after('childrenId');
            $table->string('menuName')->after('orderData');
            $table->boolean('isActive')->after('url');
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
            $table->dropColumn('orderData');
        });

        Schema::table('childrenMenuGroups', function (Blueprint $table) {
            $table->dropColumn('menuName');
            $table->dropColumn('isActive');
        });

        Schema::table('grandChildrenMenuGroups', function (Blueprint $table) {
            $table->dropColumn('menuName');
            $table->dropColumn('isActive');
        });
    }
};
