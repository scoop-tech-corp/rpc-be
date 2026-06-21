<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom verifikasi pembayaran (4-eyes principle + hash duplicate check)
 * ke tabel payment untuk: Pet Clinic, Pet Salon, Breeding, Pet Shop.
 *
 * Pet Hotel sudah dihandle di migration terpisah (200001).
 */
return new class extends Migration
{
    private array $tables = [
        'transaction_pet_clinic_payment_totals',
        'transaction_pet_salon_payment_totals',
        'transaction_breeding_payment_totals',
        'transactionpetshop',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('proofHash', 64)->nullable()->after('proofRandomName');
                $t->unsignedBigInteger('uploadedBy')->nullable()->after('proofHash');
                $t->unsignedBigInteger('confirmedBy')->nullable()->after('uploadedBy');
                $t->enum('verificationStatus', ['pending', 'verified', 'rejected'])
                  ->default('pending')->after('confirmedBy');
                $t->text('verificationNote')->nullable()->after('verificationStatus');
                $t->timestamp('verifiedAt')->nullable()->after('verificationNote');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn([
                    'proofHash', 'uploadedBy', 'confirmedBy',
                    'verificationStatus', 'verificationNote', 'verifiedAt',
                ]);
            });
        }
    }
};
