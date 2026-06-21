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
        Schema::table('transaction_pet_hotel_payment_totals', function (Blueprint $table) {
            // Hash SHA-256 file bukti pembayaran (untuk deteksi duplikat)
            $table->string('proofHash', 64)->nullable()->after('proofRandomName');

            // Siapa yang upload bukti (bisa berbeda dengan userId yang buat transaksi)
            $table->unsignedBigInteger('uploadedBy')->nullable()->after('proofHash');

            // Siapa yang konfirmasi (harus berbeda dengan uploadedBy)
            $table->unsignedBigInteger('confirmedBy')->nullable()->after('uploadedBy');

            // Status verifikasi bukti pembayaran
            $table->enum('verificationStatus', ['pending', 'verified', 'rejected'])
                  ->default('pending')
                  ->after('confirmedBy');

            // Catatan penolakan (diisi saat reject)
            $table->text('verificationNote')->nullable()->after('verificationStatus');

            // Kapan dikonfirmasi/ditolak
            $table->timestamp('verifiedAt')->nullable()->after('verificationNote');
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
