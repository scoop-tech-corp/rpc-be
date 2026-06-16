<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Perbaikan accessControl berdasarkan analisis job title.
 *
 * Roles:
 *   1 = Administrator  → Semua menu, full access
 *   2 = Manager        → Operasional + SDM + laporan, tanpa Access Control & Security Group
 *   3 = Staff          → Transaksi, Customer (lihat), Queue, Booking, Schedule
 *   4 = Customer       → Dashboard & Booking saja
 *   5 = Intership      → Sama seperti Staff tapi read-only (accessTypeId=1)
 *   6 = Office         → Finance (full), Product inventory, Customer (lihat), Report
 *   7 = Doctor         → Klinik, Treatment Plan, Customer (lihat), Queue, Booking
 *
 * accessTypeId (sesuai tabel accessType di DB):
 *   1 = Read   2 = Write (Create + Update)   3 = None   4 = Full (CRUD)
 * PENTING: jangan gunakan 3 di accessMap — nilai 3 = None/Blocked di sistem ini.
 */
return new class extends Migration
{
    /**
     * Map: menuIdentify => [ roleId => accessTypeId ]
     * Role tidak disebut = TIDAK punya akses (entry dihapus).
     */
    private function accessMap(): array
    {
        return [
            // ── GENERAL ─────────────────────────────────────────────────────
            'dashboard-menu' => [
                1 => 4, 2 => 1, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
                // Customer (4) tidak dapat akses panel manajemen
            ],
            'booking-menu' => [
                1 => 4, 2 => 1, 3 => 2, 5 => 1, 6 => 1, 7 => 2,
            ],
            'message-menu' => [
                1 => 4, 2 => 1, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
            ],

            // ── CUSTOMER ────────────────────────────────────────────────────
            'customer-dashboard' => [
                1 => 4, 2 => 1, 6 => 1,
            ],
            'customer-list' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 1,
            ],
            'customer-merge' => [
                1 => 4, 2 => 4,
            ],
            'customer-template' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'customer-import' => [
                1 => 4, 2 => 4, 6 => 2,
            ],
            'customer-material-data' => [
                1 => 4, 2 => 2,
            ],
            'customer-adii' => [
                1 => 4, 2 => 2,
            ],

            // ── STAFF ───────────────────────────────────────────────────────
            'staff-dashboard' => [
                1 => 4, 2 => 1, 6 => 1,
            ],
            'staff-list' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'staff-leave-approval' => [
                1 => 4, 2 => 4, 6 => 1,
            ],
            'staff-overwork' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'staff-access-control' => [
                1 => 4, // Administrator only
            ],
            'staff-security-group' => [
                1 => 4, // Administrator only
            ],
            'staff-schedule' => [
                1 => 4, 2 => 2, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
            ],
            'staff-material-data' => [
                1 => 4, 2 => 2,
            ],

            // ── PROMOTION ───────────────────────────────────────────────────
            'promotion-dashboard' => [
                1 => 4, 2 => 1, 6 => 1,
            ],
            'promotion-discount' => [
                1 => 4, 2 => 2, 3 => 1, 5 => 1, 6 => 1,
            ],
            'promotion-partner' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'promotion-material-data' => [
                1 => 4, 2 => 2,
            ],

            // ── SERVICE ─────────────────────────────────────────────────────
            'service-dashboard' => [
                1 => 4, 2 => 1, 6 => 1, 7 => 1,
            ],
            'service-list' => [
                1 => 4, 2 => 2, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
            ],
            'service-treatment-plan' => [
                1 => 4, 2 => 2, 5 => 1, 7 => 2,
            ],
            'service-category' => [
                1 => 4, 2 => 2,
            ],
            'service-policies' => [
                1 => 4, 2 => 2, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
            ],
            'service-template' => [
                1 => 4, 2 => 2, 7 => 1,
            ],
            'service-material-data' => [
                1 => 4, 2 => 2,
            ],
            'service-import' => [
                1 => 4, 2 => 4,
            ],

            // ── PRODUCT ─────────────────────────────────────────────────────
            'product-dashboard' => [
                1 => 4, 2 => 1, 6 => 1,
            ],
            'product-list' => [
                1 => 4, 2 => 2, 3 => 1, 5 => 1, 6 => 1, 7 => 1,
            ],
            'product-bundle' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'product-category' => [
                1 => 4, 2 => 2,
            ],
            'product-restock' => [
                1 => 4, 2 => 4, 6 => 2,
            ],
            'product-transfer' => [
                1 => 4, 2 => 4, 6 => 2,
            ],
            'product-delivery-agent' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'product-loan' => [
                1 => 4, 2 => 2, 6 => 2,
            ],
            'product-stock-opname' => [
                1 => 4, 2 => 4, 6 => 2,
            ],
            'product-material-data' => [
                1 => 4, 2 => 2,
            ],

            // ── LOCATION ────────────────────────────────────────────────────
            'location-list' => [
                1 => 4, 2 => 2, 6 => 1,
            ],
            'location-cage-management' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1,
            ],
            'location-material-data' => [
                1 => 4, 2 => 2,
            ],

            // ── FINANCE ─────────────────────────────────────────────────────
            'finance-dashboard' => [
                1 => 4, 2 => 1, 6 => 4,
            ],
            'finance-sales' => [
                1 => 4, 2 => 1, 6 => 2,
            ],
            'finance-quotation' => [
                1 => 4, 2 => 1, 6 => 2,
            ],
            'finance-expenses' => [
                1 => 4, 2 => 1, 6 => 2,
            ],
            'finance-material-data' => [
                1 => 4, 2 => 2, 6 => 2,
            ],

            // ── REPORT ──────────────────────────────────────────────────────
            'report' => [
                1 => 4, 2 => 1, 6 => 1,
            ],

            // ── QUEUE ───────────────────────────────────────────────────────
            'queue-management-menu' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 1,
            ],

            // ── TRANSACTION ─────────────────────────────────────────────────
            'transaction-dashboard' => [
                1 => 4, 2 => 1, 6 => 1,
            ],
            'transaction-pet-clinic' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 2,
            ],
            'transaction-pet-hotel' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 2,
            ],
            'transaction-pet-salon' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 2,
            ],
            'transaction-breeding' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 2,
            ],
            'transaction-pet-shop' => [
                1 => 4, 2 => 2, 3 => 2, 5 => 1, 6 => 1, 7 => 1,
            ],
            'transaction-material-data' => [
                1 => 4, 2 => 2,
            ],
        ];
    }

    public function up(): void
    {
        $accessMap = $this->accessMap();
        $now       = now();

        foreach ($accessMap as $identify => $roleMap) {
            $menuListId = DB::table('grandChildrenMenuGroups')
                ->where('identify', $identify)
                ->where('isDeleted', 0)
                ->value('id');

            if (! $menuListId) {
                continue;
            }

            // Hapus semua entry lama untuk menu ini
            DB::table('accessControl')
                ->where('menuListId', $menuListId)
                ->delete();

            // Insert entry baru sesuai peta akses
            $rows = [];
            foreach ($roleMap as $roleId => $accessTypeId) {
                $rows[] = [
                    'menuListId'   => $menuListId,
                    'roleId'       => $roleId,
                    'accessTypeId' => $accessTypeId,
                    'isDeleted'    => 0,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }

            DB::table('accessControl')->insert($rows);
        }
    }

    public function down(): void
    {
        // Rollback: hapus semua entry yang di-insert migration ini
        // (tidak restore ke kondisi lama karena data awal tidak konsisten)
        $identifies = array_keys($this->accessMap());

        $menuListIds = DB::table('grandChildrenMenuGroups')
            ->whereIn('identify', $identifies)
            ->pluck('id');

        DB::table('accessControl')
            ->whereIn('menuListId', $menuListIds)
            ->delete();
    }
};
