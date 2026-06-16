<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class SalonAutoConvertToHotel extends Command
{
    /**
     * Cek transaksi salon yang sudah "Menunggu Penjemputan" > 6 jam.
     * Jika melewati batas, status diubah ke "Dialihkan ke Pet Hotel"
     * dan staff di lokasi tersebut mendapat notifikasi internal.
     */
    protected $signature   = 'salon:auto-hotel';
    protected $description = 'Ubah status salon "Menunggu Penjemputan" > 6 jam menjadi "Dialihkan ke Pet Hotel"';

    public function handle(): int
    {
        $threshold = Carbon::now()->subHours(6);

        // Ambil transaksi yang sudah menunggu > 6 jam
        // updated_at di-set ulang setiap kali status berubah (via Eloquent),
        // sehingga saat masuk "Menunggu Penjemputan" kolom ini di-reset.
        $transactions = DB::table('transaction_pet_salons')
            ->where('status', 'Menunggu Penjemputan')
            ->where('isDeleted', 0)
            ->where('updated_at', '<=', $threshold)
            ->select('id', 'locationId', 'petId', 'customerId')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('Tidak ada transaksi yang perlu dikonversi.');
            return Command::SUCCESS;
        }

        foreach ($transactions as $trx) {
            DB::beginTransaction();
            try {
                // Update status salon
                DB::table('transaction_pet_salons')
                    ->where('id', $trx->id)
                    ->update([
                        'status'     => 'Dialihkan ke Pet Hotel',
                        'updated_at' => now(),
                    ]);

                // Log perubahan
                transactionPetSalonLog(
                    $trx->id,
                    'Pet belum dijemput lebih dari 6 jam — dialihkan ke Pet Hotel.',
                    'Proses otomatis sistem.',
                    0  // userId 0 = system
                );

                // Notifikasi internal ke Kasir (id=1) di lokasi
                sendNotificationToStaffAtLocation(
                    $trx->locationId,
                    [1], // Kasir
                    'petsalon',
                    'Pet belum dijemput lebih dari 6 jam dan telah dialihkan ke Pet Hotel. Harap tindak lanjuti.',
                    'warning'
                );

                DB::commit();
                $this->info("Transaksi ID {$trx->id} → Dialihkan ke Pet Hotel.");
            } catch (\Throwable $th) {
                DB::rollback();
                $this->error("Gagal memproses transaksi ID {$trx->id}: " . $th->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
