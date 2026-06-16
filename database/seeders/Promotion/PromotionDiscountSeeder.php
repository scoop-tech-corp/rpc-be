<?php

namespace Database\Seeders\Promotion;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Seeder untuk Promosi → Discount
 *
 * Mencakup:
 *  - Discount % untuk produk
 *  - Discount nominal (amount) untuk produk
 *  - Discount % untuk layanan
 *  - Discount nominal (amount) untuk layanan
 *
 * Bersifat idempotent: cek by name sebelum insert, aman dijalankan berulang.
 *
 * Jalankan: php artisan db:seed --class=Database\\Seeders\\Promotion\\PromotionDiscountSeeder
 */
class PromotionDiscountSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1;

        // Ambil location IDs yang aktif
        $locationIds = DB::table('locations')
            ->where('status', 1)
            ->pluck('id')
            ->toArray();

        if (empty($locationIds)) {
            $locationIds = [1]; // fallback jika tabel locations kosong
        }

        // Ambil produk (maks 3 produk pertama)
        $products = DB::table('products')
            ->where('isDeleted', 0)
            ->limit(3)
            ->get(['id', 'fullName', 'price']);

        // Ambil layanan (maks 3 layanan pertama)
        $services = DB::table('services')
            ->where('isDeleted', 0)
            ->limit(3)
            ->get(['id', 'fullName']);

        // ──────────────────────────────────────────────────────────────────
        // Definisi data promosi discount
        // ──────────────────────────────────────────────────────────────────
        $promoDefinitions = [
            // 1. Diskon % → Produk
            [
                'master' => [
                    'name'      => 'Diskon Produk 10% Spesial',
                    'type'      => 2,
                    'startDate' => '2025-01-01 00:00:00',
                    'endDate'   => '2025-12-31 23:59:59',
                    'status'    => 1,
                ],
                'target'       => 'product',
                'discountType' => 'percent',
                'percent'      => 10.0,
                'amount'       => 0.00,
                'totalMaxUsage'        => 200,
                'maxUsagePerCustomer'  => 1,
            ],

            // 2. Diskon Nominal → Produk
            [
                'master' => [
                    'name'      => 'Diskon Produk Rp 5.000',
                    'type'      => 2,
                    'startDate' => '2025-01-01 00:00:00',
                    'endDate'   => '2025-12-31 23:59:59',
                    'status'    => 1,
                ],
                'target'       => 'product',
                'discountType' => 'amount',
                'percent'      => 0.0,
                'amount'       => 5000.00,
                'totalMaxUsage'        => 100,
                'maxUsagePerCustomer'  => 1,
            ],

            // 3. Diskon % → Layanan
            [
                'master' => [
                    'name'      => 'Diskon Layanan 15% Promo Bulanan',
                    'type'      => 2,
                    'startDate' => '2025-01-01 00:00:00',
                    'endDate'   => '2025-12-31 23:59:59',
                    'status'    => 1,
                ],
                'target'       => 'service',
                'discountType' => 'percent',
                'percent'      => 15.0,
                'amount'       => 0.00,
                'totalMaxUsage'        => 150,
                'maxUsagePerCustomer'  => 2,
            ],

            // 4. Diskon Nominal → Layanan
            [
                'master' => [
                    'name'      => 'Diskon Layanan Rp 10.000',
                    'type'      => 2,
                    'startDate' => '2025-01-01 00:00:00',
                    'endDate'   => '2025-12-31 23:59:59',
                    'status'    => 1,
                ],
                'target'       => 'service',
                'discountType' => 'amount',
                'percent'      => 0.0,
                'amount'       => 10000.00,
                'totalMaxUsage'        => 100,
                'maxUsagePerCustomer'  => 1,
            ],

            // 5. Diskon % → Layanan (promo terbatas, sudah tidak aktif)
            [
                'master' => [
                    'name'      => 'Diskon Layanan 20% Flash Sale',
                    'type'      => 2,
                    'startDate' => '2024-12-01 00:00:00',
                    'endDate'   => '2024-12-31 23:59:59',
                    'status'    => 0, // tidak aktif
                ],
                'target'       => 'service',
                'discountType' => 'percent',
                'percent'      => 20.0,
                'amount'       => 0.00,
                'totalMaxUsage'        => 50,
                'maxUsagePerCustomer'  => 1,
            ],
        ];

        $now = Carbon::now();

        foreach ($promoDefinitions as $def) {
            // ── Guard: skip jika sudah ada ───────────────────────────────
            $exists = DB::table('promotionMasters')
                ->where('name', $def['master']['name'])
                ->where('isDeleted', 0)
                ->exists();

            if ($exists) {
                $this->command->line("  Skip (sudah ada): {$def['master']['name']}");
                continue;
            }

            // ── 1. Insert promotionMasters ───────────────────────────────
            $masterId = DB::table('promotionMasters')->insertGetId([
                'type'          => $def['master']['type'],
                'name'          => $def['master']['name'],
                'startDate'     => $def['master']['startDate'],
                'endDate'       => $def['master']['endDate'],
                'status'        => $def['master']['status'],
                'isDeleted'     => 0,
                'userId'        => $userId,
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // ── 2. Insert promotionLocations (semua lokasi aktif) ────────
            foreach ($locationIds as $locId) {
                DB::table('promotionLocations')->insert([
                    'promoMasterId' => $masterId,
                    'locationId'    => $locId,
                    'isDeleted'     => 0,
                    'userId'        => $userId,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            // ── 3. Insert discount detail ────────────────────────────────
            if ($def['target'] === 'product') {
                if ($products->isEmpty()) {
                    $this->command->warn("  Tidak ada produk — lewati detail untuk: {$def['master']['name']}");
                    continue;
                }
                foreach ($products as $product) {
                    DB::table('promotion_discount_products')->insert([
                        'promoMasterId'      => $masterId,
                        'discountType'       => $def['discountType'],
                        'productId'          => $product->id,
                        'amount'             => $def['amount'],
                        'percent'            => $def['percent'],
                        'totalMaxUsage'      => $def['totalMaxUsage'],
                        'maxUsagePerCustomer'=> $def['maxUsagePerCustomer'],
                        'isDeleted'          => 0,
                        'userId'             => $userId,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                }
            } else {
                if ($services->isEmpty()) {
                    $this->command->warn("  Tidak ada layanan — lewati detail untuk: {$def['master']['name']}");
                    continue;
                }
                foreach ($services as $service) {
                    DB::table('promotion_discount_services')->insert([
                        'promoMasterId'      => $masterId,
                        'discountType'       => $def['discountType'],
                        'serviceId'          => $service->id,
                        'amount'             => $def['amount'],
                        'percent'            => $def['percent'],
                        'totalMaxUsage'      => $def['totalMaxUsage'],
                        'maxUsagePerCustomer'=> $def['maxUsagePerCustomer'],
                        'isDeleted'          => 0,
                        'userId'             => $userId,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);
                }
            }

            $this->command->info("  ✓ Inserted: {$def['master']['name']} (masterId={$masterId}, target={$def['target']}, type={$def['discountType']})");
        }

        $this->command->info('PromotionDiscountSeeder selesai.');
    }
}
