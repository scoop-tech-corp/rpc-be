<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add bookingId (nullable) to all 4 service transaction tables.
 * Allows linking a transaction back to its originating booking via queue.
 * Backward compatible — existing rows remain NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactionPetClinics', function (Blueprint $table) {
            $table->unsignedBigInteger('bookingId')->nullable()->after('id');
        });

        Schema::table('transaction_pet_hotels', function (Blueprint $table) {
            $table->unsignedBigInteger('bookingId')->nullable()->after('id');
        });

        Schema::table('transaction_pet_salons', function (Blueprint $table) {
            $table->unsignedBigInteger('bookingId')->nullable()->after('id');
        });

        Schema::table('transaction_breedings', function (Blueprint $table) {
            $table->unsignedBigInteger('bookingId')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('transactionPetClinics', function (Blueprint $table) {
            $table->dropColumn('bookingId');
        });

        Schema::table('transaction_pet_hotels', function (Blueprint $table) {
            $table->dropColumn('bookingId');
        });

        Schema::table('transaction_pet_salons', function (Blueprint $table) {
            $table->dropColumn('bookingId');
        });

        Schema::table('transaction_breedings', function (Blueprint $table) {
            $table->dropColumn('bookingId');
        });
    }
};
