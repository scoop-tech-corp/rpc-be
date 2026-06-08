<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_pet_hotels', function (Blueprint $table) {
            // Service yang digunakan sebagai tarif menginap per hari (dari tabel services)
            $table->unsignedBigInteger('stayServiceId')->nullable()->after('doctorId');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_pet_hotels', function (Blueprint $table) {
            $table->dropColumn('stayServiceId');
        });
    }
};
