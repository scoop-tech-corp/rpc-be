<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductBatchesTableSeeder extends Seeder
{
    /**
     * Seed data for productBatches table.
     *
     * Batch dari Restock (productRestockId > 0, productTransferId = 0)
     * Kolom referensi:
     *   restockId  → productRestocks.id
     *   detailId   → productRestockDetails.id
     *   productId  → products.id
     */
    public function run()
    {
        \DB::table('productBatches')->delete();

        \DB::table('productBatches')->insert([
            // ── Restock #00000001 — dexa (productId=8) ──────────────────────
            [
                'id'                    => 1,
                'batchNumber'           => '#BATCH-0001',
                'productId'             => 8,
                'productRestockId'      => 1,
                'productRestockDetailId'=> 1,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0001',
                'purchaseOrderNumber'   => 'PO-2024-0001',
                'expiredDate'           => '2026-12-31',
                'sku'                   => '1234567',
                'isDeleted'             => 0,
                'userId'                => 1,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-01-10 08:00:00',
                'updated_at'            => '2024-01-10 08:00:00',
            ],

            // ── Restock #00000003 — micorn (productId=12) ───────────────────
            [
                'id'                    => 2,
                'batchNumber'           => '#BATCH-0002',
                'productId'             => 12,
                'productRestockId'      => 4,
                'productRestockDetailId'=> 5,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0002',
                'purchaseOrderNumber'   => 'PO-2024-0002',
                'expiredDate'           => '2026-06-30',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 1,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-02-05 09:15:00',
                'updated_at'            => '2024-02-05 09:15:00',
            ],

            // ── Restock #00000006 — dexa (productId=8) ──────────────────────
            [
                'id'                    => 3,
                'batchNumber'           => '#BATCH-0003',
                'productId'             => 8,
                'productRestockId'      => 9,
                'productRestockDetailId'=> 14,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0003',
                'purchaseOrderNumber'   => 'PO-2024-0003',
                'expiredDate'           => '2027-03-31',
                'sku'                   => '1234567',
                'isDeleted'             => 0,
                'userId'                => 2,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-03-12 10:30:00',
                'updated_at'            => '2024-03-12 10:30:00',
            ],

            // ── Restock #00000006 — Meo small (productId=14) ────────────────
            [
                'id'                    => 4,
                'batchNumber'           => '#BATCH-0004',
                'productId'             => 14,
                'productRestockId'      => 9,
                'productRestockDetailId'=> 15,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0003',
                'purchaseOrderNumber'   => 'PO-2024-0003',
                'expiredDate'           => '2027-03-31',
                'sku'                   => '12345',
                'isDeleted'             => 0,
                'userId'                => 2,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-03-12 10:45:00',
                'updated_at'            => '2024-03-12 10:45:00',
            ],

            // ── Restock #00000004 — whiskas teen (productId=1) ──────────────
            [
                'id'                    => 5,
                'batchNumber'           => '#BATCH-0005',
                'productId'             => 1,
                'productRestockId'      => 10,
                'productRestockDetailId'=> 16,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0004',
                'purchaseOrderNumber'   => 'PO-2024-0004',
                'expiredDate'           => '2025-12-31',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 1,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-04-01 08:00:00',
                'updated_at'            => '2024-04-01 08:00:00',
            ],

            // ── Restock #00000006 — micorn (productId=10) ───────────────────
            [
                'id'                    => 6,
                'batchNumber'           => '#BATCH-0006',
                'productId'             => 10,
                'productRestockId'      => 11,
                'productRestockDetailId'=> 18,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0005',
                'purchaseOrderNumber'   => 'PO-2024-0005',
                'expiredDate'           => '2026-09-30',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 2,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-04-20 11:00:00',
                'updated_at'            => '2024-04-20 11:00:00',
            ],

            // ── Restock #00000006 — purina (productId=3) ────────────────────
            [
                'id'                    => 7,
                'batchNumber'           => '#BATCH-0007',
                'productId'             => 3,
                'productRestockId'      => 11,
                'productRestockDetailId'=> 19,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0005',
                'purchaseOrderNumber'   => 'PO-2024-0005',
                'expiredDate'           => '2026-09-30',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 2,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-04-20 11:30:00',
                'updated_at'            => '2024-04-20 11:30:00',
            ],

            // ── Restock #00000007 — Meo small (productId=14) ────────────────
            [
                'id'                    => 8,
                'batchNumber'           => '#BATCH-0008',
                'productId'             => 14,
                'productRestockId'      => 13,
                'productRestockDetailId'=> 22,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0006',
                'purchaseOrderNumber'   => 'PO-2024-0006',
                'expiredDate'           => '2027-06-30',
                'sku'                   => '12345',
                'isDeleted'             => 0,
                'userId'                => 1,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-05-08 09:00:00',
                'updated_at'            => '2024-05-08 09:00:00',
            ],

            // ── Restock #00000007 — micorn (productId=11) ───────────────────
            [
                'id'                    => 9,
                'batchNumber'           => '#BATCH-0009',
                'productId'             => 11,
                'productRestockId'      => 13,
                'productRestockDetailId'=> 23,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0006',
                'purchaseOrderNumber'   => 'PO-2024-0006',
                'expiredDate'           => '2026-12-31',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 1,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-05-08 09:15:00',
                'updated_at'            => '2024-05-08 09:15:00',
            ],

            // ── Restock #00000008 — micorn (productId=10) ───────────────────
            [
                'id'                    => 10,
                'batchNumber'           => '#BATCH-0010',
                'productId'             => 10,
                'productRestockId'      => 15,
                'productRestockDetailId'=> 25,
                'productTransferId'     => 0,
                'transferNumber'        => '',
                'purchaseRequestNumber' => 'PR-2024-0007',
                'purchaseOrderNumber'   => 'PO-2024-0007',
                'expiredDate'           => '2027-01-31',
                'sku'                   => '123456',
                'isDeleted'             => 0,
                'userId'                => 2,
                'userUpdateId'          => null,
                'deletedBy'             => null,
                'deletedAt'             => null,
                'created_at'            => '2024-06-01 14:00:00',
                'updated_at'            => '2024-06-01 14:00:00',
            ],
        ]);
    }
}
