<?php

namespace Database\Seeders\Service;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PetHotelServiceSeeder — seed data service untuk modul Pet Hotel.
 *
 * IDEMPOTENT: aman dijalankan berulang di local, UAT, maupun prod.
 * - Service hanya di-insert jika fullName belum ada di tabel `services`.
 * - servicesLocation, servicesPrice hanya di-insert jika belum ada kombinasi (service_id, location_id).
 *
 * Cara run:
 *   php artisan db:seed --class="Database\Seeders\Service\PetHotelServiceSeeder"
 *
 * Kelompok service:
 *  A. Tarif Menginap        → wajib ada, dipakai sebagai dasar hitung biaya harian saat checkout
 *  B. Perawatan Harian      → layanan tambahan selama menginap
 *  C. Medis Selama Menginap → tindakan dokter / medis ringan
 *  D. Layanan Maternal      → khusus induk hamil / induk + anak
 */
class PetHotelServiceSeeder extends Seeder
{
    /**
     * service type:
     *  1 = Pet Hotel
     *  2 = Pet Salon / Grooming
     *  3 = Pet Clinic
     *  4 = Breeding
     */
    private const TYPE_HOTEL = 1;

    /**
     * Template service.
     *
     * Struktur:
     *  fullName    → nama tampil di list
     *  simpleName  → nama pendek (label singkat)
     *  type        → 1=Hotel, 2=Salon, 3=Clinic, 4=Breeding
     *  group       → label kelompok (untuk category)
     *  priceBase   → harga dasar (angka, tanpa format)
     *  duration    → durasi (string)
     *  unit        → satuan waktu (hari, jam, sesi)
     */
    private array $services = [

        // ─────────────────────────────────────────────────────────────
        // A. TARIF MENGINAP — dipakai sebagai stayServiceId di checkout
        // ─────────────────────────────────────────────────────────────
        [
            'fullName'   => 'Tarif Menginap Kucing S',
            'simpleName' => 'TM Kucing S',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 50000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Kucing M',
            'simpleName' => 'TM Kucing M',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 75000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Kucing L',
            'simpleName' => 'TM Kucing L',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 100000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Kucing XL',
            'simpleName' => 'TM Kucing XL',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 120000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Anjing S',
            'simpleName' => 'TM Anjing S',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 75000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Anjing M',
            'simpleName' => 'TM Anjing M',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 100000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Anjing L',
            'simpleName' => 'TM Anjing L',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 150000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Anjing XL',
            'simpleName' => 'TM Anjing XL',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 200000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap VIP',
            'simpleName' => 'TM VIP',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 250000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Tarif Menginap Maternal (Induk + Anak)',
            'simpleName' => 'TM Maternal',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Tarif Menginap',
            'priceBase'  => 150000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],

        // ─────────────────────────────────────────────────────────────
        // B. PERAWATAN HARIAN
        // ─────────────────────────────────────────────────────────────
        [
            'fullName'   => 'Mandi Kucing (Selama Menginap)',
            'simpleName' => 'Mandi Kucing',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 35000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Mandi Anjing S (Selama Menginap)',
            'simpleName' => 'Mandi Anjing S',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 50000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Mandi Anjing M (Selama Menginap)',
            'simpleName' => 'Mandi Anjing M',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 75000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Mandi Anjing L (Selama Menginap)',
            'simpleName' => 'Mandi Anjing L',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 100000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Pemotongan Kuku (Selama Menginap)',
            'simpleName' => 'Potong Kuku',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 20000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Pembersihan Telinga (Selama Menginap)',
            'simpleName' => 'Bersih Telinga',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 20000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Aktivitas & Bermain (Selama Menginap)',
            'simpleName' => 'Aktivitas Bermain',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Perawatan Harian',
            'priceBase'  => 25000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],

        // ─────────────────────────────────────────────────────────────
        // C. MEDIS SELAMA MENGINAP
        // ─────────────────────────────────────────────────────────────
        [
            'fullName'   => 'Pemeriksaan Dokter Harian',
            'simpleName' => 'Cek Dokter',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Medis Hotel',
            'priceBase'  => 50000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Vaksinasi (Selama Menginap)',
            'simpleName' => 'Vaksinasi',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Medis Hotel',
            'priceBase'  => 100000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Pemberian Obat Rutin',
            'simpleName' => 'Obat Rutin',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Medis Hotel',
            'priceBase'  => 15000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Perawatan Luka Ringan',
            'simpleName' => 'Rawat Luka',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Medis Hotel',
            'priceBase'  => 50000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Pemberian Anti Kutu & Jamur',
            'simpleName' => 'Anti Kutu',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Medis Hotel',
            'priceBase'  => 40000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],

        // ─────────────────────────────────────────────────────────────
        // D. LAYANAN MATERNAL — induk hamil / induk + anak
        // ─────────────────────────────────────────────────────────────
        [
            'fullName'   => 'Monitoring Kondisi Hamil Harian',
            'simpleName' => 'Monitor HPL',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Layanan Maternal',
            'priceBase'  => 50000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Pemeriksaan HPL (Hari Perkiraan Lahir)',
            'simpleName' => 'Cek HPL',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Layanan Maternal',
            'priceBase'  => 75000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
        [
            'fullName'   => 'Perawatan Induk Menyusui',
            'simpleName' => 'Rawat Menyusui',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Layanan Maternal',
            'priceBase'  => 60000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Perawatan Anak (Per Ekor)',
            'simpleName' => 'Rawat Anak',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Layanan Maternal',
            'priceBase'  => 30000,
            'duration'   => '1',
            'unit'       => 'hari',
        ],
        [
            'fullName'   => 'Pendampingan Proses Melahirkan',
            'simpleName' => 'Pendampingan Lahiran',
            'type'       => self::TYPE_HOTEL,
            'group'      => 'Layanan Maternal',
            'priceBase'  => 200000,
            'duration'   => '1',
            'unit'       => 'sesi',
        ],
    ];

    public function run(): void
    {
        $now    = now();
        $userId = DB::table('users')->where('roleId', 1)->value('id') ?? 1;

        // Ambil semua customer group & lokasi aktif
        $customerGroupIds = DB::table('customerGroups')->pluck('id')->toArray();
        $locationIds      = DB::table('location')->where('isDeleted', 0)->pluck('id')->toArray();

        if (empty($locationIds)) {
            $this->command->warn('Tidak ada lokasi aktif. Seeder dilewati.');
            return;
        }

        if (empty($customerGroupIds)) {
            $this->command->warn('Tidak ada customer group. Seeder dilewati.');
            return;
        }

        // Pastikan category "Pet Hotel" tersedia di serviceCategory
        $categories = $this->ensureServiceCategories($userId, $now);

        $insertedServices  = 0;
        $skippedServices   = 0;
        $insertedLocations = 0;
        $insertedPrices    = 0;

        foreach ($this->services as $svc) {

            // ── 1. Upsert service master ────────────────────────────
            $existing = DB::table('services')
                ->where('fullName', $svc['fullName'])
                ->where('isDeleted', 0)
                ->first();

            if ($existing) {
                $serviceId = $existing->id;
                $skippedServices++;
            } else {
                $serviceId = DB::table('services')->insertGetId([
                    'fullName'   => $svc['fullName'],
                    'simpleName' => $svc['simpleName'],
                    'status'     => 1,
                    'type'       => $svc['type'],
                    'userId'     => $userId,
                    'isDeleted'  => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertedServices++;

                // ── 2. Link service ke category ─────────────────────
                $catId = $categories[$svc['group']] ?? null;
                if ($catId) {
                    $catExists = DB::table('servicesCategoryList')
                        ->where('service_id', $serviceId)
                        ->where('category_id', $catId)
                        ->where('isDeleted', 0)
                        ->exists();

                    if (!$catExists) {
                        DB::table('servicesCategoryList')->insert([
                            'service_id'  => $serviceId,
                            'category_id' => $catId,
                            'userId'      => $userId,
                            'isDeleted'   => 0,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ]);
                    }
                }
            }

            // ── 3. servicesLocation — link ke semua lokasi ──────────
            foreach ($locationIds as $locationId) {
                $locExists = DB::table('servicesLocation')
                    ->where('service_id', $serviceId)
                    ->where('location_id', $locationId)
                    ->where('isDeleted', 0)
                    ->exists();

                if (!$locExists) {
                    DB::table('servicesLocation')->insert([
                        'service_id'  => $serviceId,
                        'location_id' => $locationId,
                        'userId'      => $userId,
                        'isDeleted'   => 0,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                    $insertedLocations++;
                }
            }

            // ── 4. servicesPrice — satu harga per (service, group, lokasi) ──
            foreach ($customerGroupIds as $cgId) {
                foreach ($locationIds as $locationId) {
                    $priceExists = DB::table('servicesPrice')
                        ->where('service_id', $serviceId)
                        ->where('customer_group_id', $cgId)
                        ->where('location_id', $locationId)
                        ->where('isDeleted', 0)
                        ->exists();

                    if (!$priceExists) {
                        DB::table('servicesPrice')->insert([
                            'service_id'        => $serviceId,
                            'customer_group_id' => $cgId,
                            'location_id'       => $locationId,
                            'price'             => number_format($svc['priceBase'], 0, ',', ','),
                            'duration'          => $svc['duration'],
                            'unit'              => $svc['unit'],
                            'title'             => $svc['simpleName'],
                            'userId'            => $userId,
                            'isDeleted'         => 0,
                            'created_at'        => $now,
                            'updated_at'        => $now,
                        ]);
                        $insertedPrices++;
                    }
                }
            }
        }

        $this->command->info(sprintf(
            'PetHotelServiceSeeder: %d service baru, %d dilewati | %d servicesLocation | %d servicesPrice',
            $insertedServices,
            $skippedServices,
            $insertedLocations,
            $insertedPrices
        ));
    }

    /**
     * Pastikan kategori service untuk pet hotel tersedia.
     * Return map ['group_label' => category_id].
     */
    private function ensureServiceCategories(int $userId, $now): array
    {
        $groups = [
            'Tarif Menginap'   => 'Tarif Menginap Pet Hotel',
            'Perawatan Harian' => 'Perawatan Harian Pet Hotel',
            'Medis Hotel'      => 'Medis Selama Menginap',
            'Layanan Maternal' => 'Layanan Maternal (Induk/Hamil)',
        ];

        $map = [];
        foreach ($groups as $groupKey => $catName) {
            $cat = DB::table('serviceCategory')
                ->where('categoryName', $catName)
                ->where('isDeleted', 0)
                ->first();

            if ($cat) {
                $map[$groupKey] = $cat->id;
            } else {
                $id = DB::table('serviceCategory')->insertGetId([
                    'categoryName' => $catName,
                    'userId'       => $userId,
                    'isDeleted'    => 0,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
                $map[$groupKey] = $id;
                $this->command->line("  Category baru: [{$id}] {$catName}");
            }
        }

        return $map;
    }
}
