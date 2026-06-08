<?php

namespace Database\Seeders\Cage;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CageSeeder — seed data kandang (tabel `cages`).
 *
 * IDEMPOTENT: aman dijalankan berulang kali di local, UAT, maupun prod.
 * Data hanya di-insert jika kombinasi (locationId + cageName) belum ada.
 *
 * Cara run:
 *   php artisan db:seed --class="Database\Seeders\Cage\CageSeeder"
 */
class CageSeeder extends Seeder
{
    // Template kandang per tipe.
    // Setiap lokasi akan mendapat satu set kandang berikut.
    private array $templates = [
        // ── Hotel ──────────────────────────────────────────────────
        ['cageName' => 'Hotel-S-01', 'type' => 'hotel',    'size' => 'S',  'capacity' => 1, 'amount' => 1],
        ['cageName' => 'Hotel-S-02', 'type' => 'hotel',    'size' => 'S',  'capacity' => 1, 'amount' => 1],
        ['cageName' => 'Hotel-M-01', 'type' => 'hotel',    'size' => 'M',  'capacity' => 2, 'amount' => 1],
        ['cageName' => 'Hotel-M-02', 'type' => 'hotel',    'size' => 'M',  'capacity' => 2, 'amount' => 1],
        ['cageName' => 'Hotel-L-01', 'type' => 'hotel',    'size' => 'L',  'capacity' => 3, 'amount' => 1],
        ['cageName' => 'Hotel-XL-01','type' => 'hotel',    'size' => 'XL', 'capacity' => 4, 'amount' => 1],

        // ── Breeding ───────────────────────────────────────────────
        ['cageName' => 'Breed-S-01', 'type' => 'breeding', 'size' => 'S',  'capacity' => 2, 'amount' => 1],
        ['cageName' => 'Breed-S-02', 'type' => 'breeding', 'size' => 'S',  'capacity' => 2, 'amount' => 1],
        ['cageName' => 'Breed-M-01', 'type' => 'breeding', 'size' => 'M',  'capacity' => 4, 'amount' => 1],
        ['cageName' => 'Breed-L-01', 'type' => 'breeding', 'size' => 'L',  'capacity' => 6, 'amount' => 1],

        // ── Salon ──────────────────────────────────────────────────
        ['cageName' => 'Salon-S-01', 'type' => 'salon',    'size' => 'S',  'capacity' => 1, 'amount' => 1],
        ['cageName' => 'Salon-M-01', 'type' => 'salon',    'size' => 'M',  'capacity' => 2, 'amount' => 1],

        // ── General ────────────────────────────────────────────────
        ['cageName' => 'General-01', 'type' => 'general',  'size' => null, 'capacity' => 2, 'amount' => 1],

        // ── Maternal (khusus induk hamil / induk + anak) ───────────
        ['cageName' => 'Maternal-M-01', 'type' => 'maternal', 'size' => 'M',  'capacity' => 4, 'amount' => 1],
        ['cageName' => 'Maternal-L-01', 'type' => 'maternal', 'size' => 'L',  'capacity' => 6, 'amount' => 1],
    ];

    public function run(): void
    {
        // Ambil userId admin/superadmin pertama yang ada — fallback ke 1
        $userId = DB::table('users')
            ->where('roleId', 1)
            ->value('id') ?? 1;

        // Ambil semua lokasi aktif yang ada di DB (dinamis, tidak hardcode ID)
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if (empty($locations)) {
            $this->command->warn('Tidak ada data lokasi. CageSeeder dilewati.');
            return;
        }

        $inserted = 0;
        $skipped  = 0;
        $now      = now();

        foreach ($locations as $locationId) {
            foreach ($this->templates as $tpl) {
                // Cek apakah kandang dengan nama + lokasi ini sudah ada
                $exists = DB::table('cages')
                    ->where('locationId', $locationId)
                    ->where('cageName', $tpl['cageName'])
                    ->where('isDeleted', 0)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                DB::table('cages')->insert([
                    'locationId'      => $locationId,
                    'cageName'        => $tpl['cageName'],
                    'type'            => $tpl['type'],
                    'size'            => $tpl['size'],
                    'status'          => 1,
                    'conditionStatus' => 'baik',
                    'capacity'        => $tpl['capacity'],
                    'amount'          => $tpl['amount'],
                    'notes'           => null,
                    'isDeleted'       => 0,
                    'userId'          => $userId,
                    'userUpdateId'    => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                $inserted++;
            }
        }

        $total = count($locations) * count($this->templates);
        $this->command->info(
            "CageSeeder: {$inserted} kandang ditambahkan, {$skipped} dilewati (sudah ada). " .
            "Total lokasi: " . count($locations) . ", template per lokasi: " . count($this->templates) . "."
        );
    }
}
