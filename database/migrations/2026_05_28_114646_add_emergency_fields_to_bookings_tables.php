<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookingsPetHotels', function (Blueprint $table) {
            $table->string('emergencyPhoneNumber')->nullable()->after('emergencyContactName');
        });

        Schema::table('bookingsPetSalons', function (Blueprint $table) {
            $table->string('emergencyContactName')->nullable()->after('skinSensitivity');
            $table->string('emergencyPhoneNumber')->nullable()->after('emergencyContactName');
        });

        Schema::table('bookingsBreedings', function (Blueprint $table) {
            $table->string('emergencyContactName')->nullable()->after('healthClearance');
            $table->string('emergencyPhoneNumber')->nullable()->after('emergencyContactName');
        });
    }

    public function down()
    {
        Schema::table('bookingsPetHotels', function (Blueprint $table) {
            $table->dropColumn('emergencyPhoneNumber');
        });

        Schema::table('bookingsPetSalons', function (Blueprint $table) {
            $table->dropColumn(['emergencyContactName', 'emergencyPhoneNumber']);
        });

        Schema::table('bookingsBreedings', function (Blueprint $table) {
            $table->dropColumn(['emergencyContactName', 'emergencyPhoneNumber']);
        });
    }
};
