<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed semua entry accessReportMenus yang belum ada untuk roleId=1 (Administrator).
 *
 * Idempotent: setiap entry dicek dulu sebelum insert — aman dijalankan
 * berulang kali atau di environment yang sebagian datanya sudah ada.
 *
 * Kategori yang sebelumnya sudah di-seed via migration terpisah:
 *   Products  : batches, expiry
 *   Sales     : by-item-type, package-summary, customer-spend,
 *               daily-reconciliation, refunds
 *
 * Migration ini menambah semua yang masih terkunci di screenshot produksi:
 *   Booking   : semua (by-location, by-status, by-cancellation-reason,
 *               list, diagnosis-list, by-diagnosis-species-gender)
 *   Customer  : semua (growth, growth-by-group, total, leaving, list,
 *               referral-spend, sub-account-list)
 *   Deposit   : summary, list
 *   Expenses  : summary, list
 *   Products  : stock-count, low-stock, no-stock, cost, reminders
 *   Sales     : summary, details, items, discount-summary,
 *               payment-summary, payment-list, unpaid,
 *               by-service, by-product, net-income,
 *               daily-audit, staff-service-sales
 *   Staff     : login, late, leave, performance
 */
return new class extends Migration
{
    private int $roleId       = 1;   // Administrator
    private int $accessTypeId = 3;   // nilai konvensi laporan (FE hanya cek keberadaan record)
    private int $userId       = 1;

    private array $entries = [
        // ── Booking ──────────────────────────────────────────────────────────
        ['groupName' => 'Booking', 'menuName' => 'By Location',               'url' => 'report-detail?type=booking&detail=by-location'],
        ['groupName' => 'Booking', 'menuName' => 'By Status',                 'url' => 'report-detail?type=booking&detail=by-status'],
        ['groupName' => 'Booking', 'menuName' => 'By Cancellation Reason',    'url' => 'report-detail?type=booking&detail=by-cancellation-reason'],
        ['groupName' => 'Booking', 'menuName' => 'List',                      'url' => 'report-detail?type=booking&detail=list'],
        ['groupName' => 'Booking', 'menuName' => 'Diagnosis List',            'url' => 'report-detail?type=booking&detail=diagnosis-list'],
        ['groupName' => 'Booking', 'menuName' => 'By Diagnosis Species & Gender', 'url' => 'report-detail?type=booking&detail=by-diagnosis-species-gender'],

        // ── Customer ─────────────────────────────────────────────────────────
        ['groupName' => 'Customer', 'menuName' => 'Growth',          'url' => 'report-detail?type=customer&detail=growth'],
        ['groupName' => 'Customer', 'menuName' => 'Growth by Group', 'url' => 'report-detail?type=customer&detail=growth-by-group'],
        ['groupName' => 'Customer', 'menuName' => 'Total',           'url' => 'report-detail?type=customer&detail=total'],
        ['groupName' => 'Customer', 'menuName' => 'Leaving',         'url' => 'report-detail?type=customer&detail=leaving'],
        ['groupName' => 'Customer', 'menuName' => 'List',            'url' => 'report-detail?type=customer&detail=list'],
        ['groupName' => 'Customer', 'menuName' => 'Referral Spend',  'url' => 'report-detail?type=customer&detail=referral-spend'],
        ['groupName' => 'Customer', 'menuName' => 'Sub Account List','url' => 'report-detail?type=customer&detail=sub-account-list'],

        // ── Deposit ──────────────────────────────────────────────────────────
        ['groupName' => 'Deposit', 'menuName' => 'Summary', 'url' => 'report-detail?type=deposit&detail=summary'],
        ['groupName' => 'Deposit', 'menuName' => 'List',    'url' => 'report-detail?type=deposit&detail=list'],

        // ── Expenses ─────────────────────────────────────────────────────────
        ['groupName' => 'Expenses', 'menuName' => 'Summary', 'url' => 'report-detail?type=expenses&detail=summary'],
        ['groupName' => 'Expenses', 'menuName' => 'List',    'url' => 'report-detail?type=expenses&detail=list'],

        // ── Products (yang belum ada) ─────────────────────────────────────
        ['groupName' => 'Products', 'menuName' => 'Stock Count', 'url' => 'report-detail?type=products&detail=stock-count'],
        ['groupName' => 'Products', 'menuName' => 'Low Stock',   'url' => 'report-detail?type=products&detail=low-stock'],
        ['groupName' => 'Products', 'menuName' => 'No Stock',    'url' => 'report-detail?type=products&detail=no-stock'],
        ['groupName' => 'Products', 'menuName' => 'Cost',        'url' => 'report-detail?type=products&detail=cost'],
        ['groupName' => 'Products', 'menuName' => 'Reminders',   'url' => 'report-detail?type=products&detail=reminders'],

        // ── Sales (yang belum ada) ───────────────────────────────────────
        ['groupName' => 'Sales', 'menuName' => 'Summary',          'url' => 'report-detail?type=sales&detail=summary'],
        ['groupName' => 'Sales', 'menuName' => 'Details',          'url' => 'report-detail?type=sales&detail=details'],
        ['groupName' => 'Sales', 'menuName' => 'Items',            'url' => 'report-detail?type=sales&detail=items'],
        ['groupName' => 'Sales', 'menuName' => 'Discount Summary', 'url' => 'report-detail?type=sales&detail=discount-summary'],
        ['groupName' => 'Sales', 'menuName' => 'Payment Summary',  'url' => 'report-detail?type=sales&detail=payment-summary'],
        ['groupName' => 'Sales', 'menuName' => 'Payment List',     'url' => 'report-detail?type=sales&detail=payment-list'],
        ['groupName' => 'Sales', 'menuName' => 'Unpaid',           'url' => 'report-detail?type=sales&detail=unpaid'],
        ['groupName' => 'Sales', 'menuName' => 'Sales by Service', 'url' => 'report-detail?type=sales&detail=by-service'],
        ['groupName' => 'Sales', 'menuName' => 'Sales by Product', 'url' => 'report-detail?type=sales&detail=by-product'],
        ['groupName' => 'Sales', 'menuName' => 'Net Income',       'url' => 'report-detail?type=sales&detail=net-income'],
        ['groupName' => 'Sales', 'menuName' => 'Daily Audit',      'url' => 'report-detail?type=sales&detail=daily-audit'],
        ['groupName' => 'Sales', 'menuName' => 'Staff Service Sales', 'url' => 'report-detail?type=sales&detail=staff-service-sales'],

        // ── Staff ────────────────────────────────────────────────────────────
        ['groupName' => 'Staff', 'menuName' => 'Staff Login',       'url' => 'report-detail?type=staff&detail=login'],
        ['groupName' => 'Staff', 'menuName' => 'Staff Late',        'url' => 'report-detail?type=staff&detail=late'],
        ['groupName' => 'Staff', 'menuName' => 'Staff Leave',       'url' => 'report-detail?type=staff&detail=leave'],
        ['groupName' => 'Staff', 'menuName' => 'Staff Performance', 'url' => 'report-detail?type=staff&detail=performance'],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->entries as $entry) {
            $exists = DB::table('accessReportMenus')
                ->where('url',    $entry['url'])
                ->where('roleId', $this->roleId)
                ->where('isDeleted', 0)
                ->exists();

            if ($exists) continue;

            DB::table('accessReportMenus')->insert([
                'groupName'    => $entry['groupName'],
                'menuName'     => $entry['menuName'],
                'url'          => $entry['url'],
                'roleId'       => $this->roleId,
                'accessTypeId' => $this->accessTypeId,
                'userId'       => $this->userId,
                'isDeleted'    => 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->entries as $entry) {
            DB::table('accessReportMenus')
                ->where('url',    $entry['url'])
                ->where('roleId', $this->roleId)
                ->delete();
        }
    }
};
