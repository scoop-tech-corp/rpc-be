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
            $table->dateTime('homeTime')->nullable()->after('presentTime');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->time('duration')->nullable()->after('homeTime');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('homeLongitude')->nullable()->after('longitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('longitude', 'presentLongitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('homeLatitude')->nullable()->after('latitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('latitude', 'presentLatitude');
        });


        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->integer('statusHome')->nullable()->after('status');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('status', 'statusPresent');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('reasonHome')->nullable()->after('reason');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('reason', 'reasonPresent');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('realImageNameHome')->nullable()->after('realImageName');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('realImageName', 'realImageNamePresent');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('imagePathHome')->nullable()->after('imagePath');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('imagePath', 'imagePathPresent');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('address');
        });


        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('cityHome')->nullable()->after('city');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('city', 'cityPresent');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('provinceHome')->nullable()->after('province');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('province', 'provincePresent');
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
            $table->dropColumn('homeTime');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('duration');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('homeLongitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('presentLongitude', 'longitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('homeLatitude');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('presentLatitude', 'latitude');
        });


        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('statusHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('statusPresent', 'status');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('reasonHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('reasonPresent', 'reason');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('realImageNameHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('realImageNamePresent', 'realImageName');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('imagePathHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('imagePathPresent', 'imagePath');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->string('address')->after('imagePath');
        });


        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('cityHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('cityPresent', 'city');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->dropColumn('provinceHome');
        });

        Schema::table('staffAbsents', function (Blueprint $table) {
            $table->renameColumn('provincePresent', 'province');
        });
    }
};
