<?php

namespace Database\Seeders\Service;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PetHotelTreatmentPlanSeeder — seed Treatment Plan untuk modul Pet Hotel.
 *
 * IDEMPOTENT: aman dijalankan berulang di local, UAT, maupun prod.
 * - Treatment hanya di-insert jika (name + location_id + diagnose_id) belum ada.
 * - Items di-insert jika treatment baru di-insert (skip jika treatment sudah ada).
 *
 * Cara run:
 *   php artisan db:seed --class="Database\Seeders\Service\PetHotelTreatmentPlanSeeder"
 *
 * Catatan struktur treatmentsItems:
 *   - task_id    : referensi ke tabel `task` (aktifitas: kasih makan, cek infus, dll)
 *   - service_id : referensi ke tabel `services`
 *   - product_type + product_name : produk sell/clinic (teks bebas)
 *   - frequency_id: 1=1x/hari, 2=2x/hari, 3=3x/hari, 4=4x/hari, 5=setiap 2 hari, 7=1x/minggu
 *   - duration   : jumlah hari item ini berlaku
 *   - start      : hari ke berapa mulai diterapkan
 *   - quantity   : jumlah per pemberian
 */
class PetHotelTreatmentPlanSeeder extends Seeder
{
    // ── Frekuensi (dari tabel servicesFrequency) ──────────────────────────────
    private const FREQ_1X_HARI    = 1;   // once per day
    private const FREQ_2X_HARI    = 2;   // twice per day
    private const FREQ_3X_HARI    = 3;   // thrice per day
    private const FREQ_4X_HARI    = 4;   // four times per day
    private const FREQ_2_HARI     = 5;   // every other day
    private const FREQ_1X_MINGGU  = 7;   // once a week

    // ── Task (dari tabel task) ────────────────────────────────────────────────
    private const TASK_KASIH_MAKAN = 1;

    // ── Service ID (dari tabel services, hasil PetHotelServiceSeeder) ─────────
    // (nilai ini di-resolve secara dinamis saat run, lihat method resolveServiceIds)

    /**
     * Template treatment plan + items-nya.
     * service_name dipakai untuk lookup ID dinamis ke DB.
     *
     * Setiap plan akan dibuat untuk SEMUA lokasi aktif.
     */
    private function getTemplates(array $svcMap): array
    {
        return [

            // ─── 1. PERAWATAN STANDAR KUCING ──────────────────────────────────
            [
                'name'   => 'Perawatan Standar Kucing',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_3X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Berikan makanan basah/kering sesuai berat badan kucing',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Mandi Kucing (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '1',
                        'start'        => '3',
                        'quantity'     => 1,
                        'notes'        => 'Mandi di hari ke-3 menginap',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemotongan Kuku (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Potong kuku di hari pertama check-in jika diperlukan',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pembersihan Telinga (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Bersihkan telinga di hari pertama check-in',
                    ],
                ],
            ],

            // ─── 2. PERAWATAN STANDAR ANJING S (1–3 Kg) ─────────────────────
            [
                'name'   => 'Perawatan Standar Anjing S (1-3 Kg)',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Makanan kering porsi S (anjing kecil 1-3 Kg)',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Mandi Anjing S (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '1',
                        'start'        => '3',
                        'quantity'     => 1,
                        'notes'        => 'Mandi di hari ke-3 menginap',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemotongan Kuku (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => '',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Aktivitas & Bermain (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Sesi bermain 15-20 menit per hari',
                    ],
                ],
            ],

            // ─── 3. PERAWATAN STANDAR ANJING M (3–10 Kg) ────────────────────
            [
                'name'   => 'Perawatan Standar Anjing M (3-10 Kg)',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 2,
                        'notes'        => 'Makanan kering porsi M (anjing medium 3-10 Kg)',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Mandi Anjing M (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '1',
                        'start'        => '3',
                        'quantity'     => 1,
                        'notes'        => 'Mandi di hari ke-3 menginap',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemotongan Kuku (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => '',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Aktivitas & Bermain (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Sesi bermain 20-30 menit per hari',
                    ],
                ],
            ],

            // ─── 4. PERAWATAN STANDAR ANJING L (>10 Kg) ─────────────────────
            [
                'name'   => 'Perawatan Standar Anjing L (>10 Kg)',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 3,
                        'notes'        => 'Makanan kering porsi L (anjing besar >10 Kg)',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Mandi Anjing L (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '1',
                        'start'        => '3',
                        'quantity'     => 1,
                        'notes'        => 'Mandi di hari ke-3 menginap',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemotongan Kuku (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => '',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Aktivitas & Bermain (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Sesi bermain dan jalan-jalan 30 menit per hari',
                    ],
                ],
            ],

            // ─── 5. PERAWATAN PET HAMIL ──────────────────────────────────────
            [
                'name'   => 'Perawatan Pet Hamil',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_3X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Makanan bergizi tinggi protein untuk induk hamil — porsi lebih besar dari biasanya',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Monitoring Kondisi Hamil Harian',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Catat berat badan, nafsu makan, dan kondisi perut setiap hari',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemeriksaan HPL (Hari Perkiraan Lahir)',
                        'frequency_id' => self::FREQ_2_HARI,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Cek HPL setiap 2 hari, segera informasikan ke owner jika HPL < 5 hari',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemeriksaan Dokter Harian',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Dokter memantau kondisi kehamilan dan detak jantung janin setiap hari',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemberian Anti Kutu & Jamur',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Gunakan produk yang aman untuk pet hamil (konsultasi dokter)',
                    ],
                ],
            ],

            // ─── 6. PERAWATAN INDUK + ANAK (MENYUSUI) ───────────────────────
            [
                'name'   => 'Perawatan Induk Menyusui + Anak',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_4X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 2,
                        'notes'        => 'Porsi lebih besar untuk induk menyusui — nutrisi tinggi kalori dan protein',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Perawatan Induk Menyusui',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Pastikan puting susu bersih dan tidak tersumbat, cek kondisi induk',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Perawatan Anak (Per Ekor)',
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Cek kondisi anak: berat badan, suhu tubuh, dan aktivitas menyusu — quantity disesuaikan jumlah anak',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemeriksaan Dokter Harian',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Dokter memantau induk dan perkembangan anak setiap hari',
                    ],
                ],
            ],

            // ─── 7. PERAWATAN VIP ─────────────────────────────────────────────
            [
                'name'   => 'Perawatan VIP',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_4X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Makanan premium 4x sehari — pagi, siang, sore, malam',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Mandi Kucing (Selama Menginap)',
                        'frequency_id' => self::FREQ_2_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Mandi setiap 2 hari sekali dengan shampoo premium',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemotongan Kuku (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => '',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pembersihan Telinga (Selama Menginap)',
                        'frequency_id' => self::FREQ_1X_MINGGU,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => '',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Aktivitas & Bermain (Selama Menginap)',
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Sesi bermain pagi dan sore, 20-30 menit per sesi',
                    ],
                    [
                        'type'         => 'service',
                        'service_name' => 'Pemeriksaan Dokter Harian',
                        'frequency_id' => self::FREQ_1X_HARI,
                        'duration'     => '7',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Pemeriksaan rutin harian oleh dokter hewan',
                    ],
                ],
            ],

            // ─── 8. PERAWATAN DASAR (Titip Harian / Short Stay) ─────────────
            [
                'name'   => 'Perawatan Dasar (Titip Harian)',
                'column' => 6,
                'items'  => [
                    [
                        'type'         => 'task',
                        'task_id'      => self::TASK_KASIH_MAKAN,
                        'frequency_id' => self::FREQ_2X_HARI,
                        'duration'     => '1',
                        'start'        => '1',
                        'quantity'     => 1,
                        'notes'        => 'Makan pagi dan sore — cocok untuk titip 1 hari',
                    ],
                ],
            ],

        ];
    }

    public function run(): void
    {
        $now    = now();
        $userId = DB::table('users')->where('roleId', 1)->value('id') ?? 1;

        // Pastikan diagnosa "Pet Hotel" tersedia
        $diagnoseId = $this->ensureDiagnoseId($userId, $now);

        // Resolve service IDs dari DB berdasarkan fullName
        $svcMap = $this->resolveServiceIds();

        $locationIds = DB::table('location')
            ->where('isDeleted', 0)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        if (empty($locationIds)) {
            $this->command->warn('Tidak ada lokasi aktif. Seeder dilewati.');
            return;
        }

        $templates        = $this->getTemplates($svcMap);
        $insertedPlans    = 0;
        $skippedPlans     = 0;
        $insertedItems    = 0;

        foreach ($locationIds as $locationId) {
            foreach ($templates as $tpl) {

                // ── Idempotency check ───────────────────────────────────────
                $exists = DB::table('treatments')
                    ->where('name', $tpl['name'])
                    ->where('location_id', $locationId)
                    ->where('diagnose_id', $diagnoseId)
                    ->where('isDeleted', 0)
                    ->exists();

                if ($exists) {
                    $skippedPlans++;
                    continue;
                }

                // ── Insert treatment plan ──────────────────────────────────
                $treatmentId = DB::table('treatments')->insertGetId([
                    'name'        => $tpl['name'],
                    'location_id' => $locationId,
                    'diagnose_id' => $diagnoseId,
                    'status'      => 2,          // active
                    'column'      => $tpl['column'] ?? 6,
                    'userId'      => $userId,
                    'isDeleted'   => 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $insertedPlans++;

                // ── Insert treatment items ─────────────────────────────────
                foreach ($tpl['items'] as $item) {
                    $row = [
                        'treatments_id' => $treatmentId,
                        'frequency_id'  => $item['frequency_id'],
                        'duration'      => $item['duration'],
                        'start'         => $item['start'],
                        'quantity'      => $item['quantity'],
                        'notes'         => $item['notes'] ?? null,
                        'product_type'  => null,
                        'product_name'  => null,
                        'service_id'    => null,
                        'task_id'       => null,
                        'userId'        => $userId,
                        'isDeleted'     => 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];

                    if ($item['type'] === 'task') {
                        $row['task_id'] = $item['task_id'];
                    } elseif ($item['type'] === 'service') {
                        $serviceId = $svcMap[$item['service_name']] ?? null;
                        if (!$serviceId) {
                            $this->command->warn("  Service tidak ditemukan: [{$item['service_name']}] — item dilewati.");
                            continue;
                        }
                        $row['service_id'] = $serviceId;
                    } elseif ($item['type'] === 'product') {
                        $row['product_type'] = $item['product_type'];
                        $row['product_name'] = $item['product_name'];
                    }

                    DB::table('treatmentsItems')->insert($row);
                    $insertedItems++;
                }
            }
        }

        $this->command->info(sprintf(
            'PetHotelTreatmentPlanSeeder: %d treatment plan baru, %d dilewati | %d items',
            $insertedPlans,
            $skippedPlans,
            $insertedItems
        ));
        $this->command->line(sprintf(
            '  → %d lokasi × %d template = %d total plans',
            count($locationIds),
            count($templates),
            count($locationIds) * count($templates)
        ));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Pastikan diagnosa "Pet Hotel" ada, return id-nya */
    private function ensureDiagnoseId(int $userId, $now): int
    {
        $diag = DB::table('diagnose')
            ->where('name', 'Pet Hotel')
            ->where('isDeleted', 0)
            ->first();

        if ($diag) {
            return (int) $diag->id;
        }

        $id = DB::table('diagnose')->insertGetId([
            'name'       => 'Pet Hotel',
            'userId'     => $userId,
            'isDeleted'  => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->command->line("  Diagnosa baru dibuat: [Pet Hotel] id={$id}");
        return $id;
    }

    /** Buat map ['fullName' => id] dari tabel services untuk lookup item */
    private function resolveServiceIds(): array
    {
        $rows = DB::table('services')
            ->where('isDeleted', 0)
            ->pluck('id', 'fullName')
            ->toArray();

        return $rows; // ['Mandi Kucing (Selama Menginap)' => 47, ...]
    }
}
