<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom untuk sistem verifikasi pembayaran 2 langkah (4-eyes principle)
 * pada tabel transaction_pet_hotel_payment_totals.
 *
 * Flow baru:
 *   1. Staff upload bukti → proofHash dicek duplikat → status = 'pending'
 *   2. Finance/Manager (orang berbeda) konfirmasi → 4-eyes check → status = 'verified'
 *      atau tolak → status = 'rejected' + verificationNote
 */
return new class extends Migration
{
    public function up(): void
    {
        $table = 'transaction_pet_hotel_payment_totals';

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (!Schema::hasColumn($table, 'proofHash')) {
                $t->string('proofHash', 64)->nullable()->after('proofRandomName');
            }
            if (!Schema::hasColumn($table, 'uploadedBy')) {
                $t->unsignedBigInteger('uploadedBy')->nullable()->after('proofHash');
            }
            if (!Schema::hasColumn($table, 'confirmedBy')) {
                $t->unsignedBigInteger('confirmedBy')->nullable()->after('uploadedBy');
            }
            if (!Schema::hasColumn($table, 'verificationStatus')) {
                $t->enum('verificationStatus', ['pending', 'verified', 'rejected'])
                    ->default('pending')
                    ->after('confirmedBy');
            }
            if (!Schema::hasColumn($table, 'verificationNote')) {
                $t->text('verificationNote')->nullable()->after('verificationStatus');
            }
            if (!Schema::hasColumn($table, 'verifiedAt')) {
                $t->timestamp('verifiedAt')->nullable()->after('verificationNote');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaction_pet_hotel_payment_totals', function (Blueprint $table) {
            $table->dropColumn([
                'proofHash',
                'uploadedBy',
                'confirmedBy',
                'verificationStatus',
                'verificationNote',
                'verifiedAt',
            ]);
        });
    }
};
