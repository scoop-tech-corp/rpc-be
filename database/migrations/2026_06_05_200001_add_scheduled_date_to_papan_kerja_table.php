<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_pet_hotel_papan_kerja', function (Blueprint $table) {
            // Tanggal jadwal aktivitas (per hari menginap)
            $table->date('scheduledDate')->nullable()->after('type');
        });
    }

    public function down()
    {
        Schema::table('transaction_pet_hotel_papan_kerja', function (Blueprint $table) {
            $table->dropColumn('scheduledDate');
        });
    }
};
