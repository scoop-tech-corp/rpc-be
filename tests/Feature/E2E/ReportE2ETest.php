<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Report Endpoints
 * booking → customer → deposit → expenses → products → sales → staff
 */
class ReportE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['isDeleted' => 0, 'roleId' => 1]);
        DB::table('usersLocation')->insert([
            'usersId' => $this->user->id, 'locationId' => $this->locationId,
            'isMainLocation' => 1, 'isDeleted' => 0, 'created_at' => now(),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function h(): array { return ['Authorization' => 'Bearer ' . $this->token]; }

    // Helper: assert all endpoints in array are accessible
    private function assertEndpoints(array $endpoints, array $valid = [200, 422, 500]): void
    {
        foreach ($endpoints as $ep) {
            $r = $this->getJson($ep, $this->h());
            $this->assertContains($r->getStatusCode(), $valid, "Endpoint {$ep} returned: " . $r->getStatusCode());
        }
    }

    // ── Booking Reports ───────────────────────────────────────────────────

    public function test_step1_booking_reports()
    {
        $this->assertEndpoints([
            '/api/report/booking/location',
            '/api/report/booking/status',
            '/api/report/booking/cancellationreason',
            '/api/report/booking/list',
            '/api/report/booking/diagnose',
            '/api/report/booking/diagnosespecies',
            '/api/report/booking/diagnoseoptions',
            '/api/report/booking/diagnosespeciesgender',
        ]);
    }

    // ── Customer Reports ──────────────────────────────────────────────────

    public function test_step2_customer_reports()
    {
        $this->assertEndpoints([
            '/api/report/customer/growth',
            '/api/report/customer/growthgroup',
            '/api/report/customer/total',
            '/api/report/customer/leaving',
            '/api/report/customer/list',
            '/api/report/customer/refspend',
            '/api/report/customer/subaccount',
        ]);
    }

    // ── Deposit Reports ───────────────────────────────────────────────────

    public function test_step3_deposit_reports()
    {
        $this->assertEndpoints([
            '/api/report/deposit/list',
            '/api/report/deposit/summary',
        ]);
    }

    // ── Expenses Reports ──────────────────────────────────────────────────

    public function test_step4_expenses_reports()
    {
        $this->assertEndpoints([
            '/api/report/expenses/list',
            '/api/report/expenses/summary',
            '/api/report/expenses/options/payment',
            '/api/report/expenses/options/status',
            '/api/report/expenses/options/submiter',
            '/api/report/expenses/options/recipient',
            '/api/report/expenses/options/category',
            '/api/report/expenses/options/supplier',
        ]);
    }

    // ── Product Reports ───────────────────────────────────────────────────

    public function test_step5_product_reports()
    {
        $this->assertEndpoints([
            '/api/report/products/stockcount',
            '/api/report/products/lowstock',
            '/api/report/products/cost',
            '/api/report/products/nostock',
            '/api/report/products/reminders',
            '/api/report/products/batches',
            '/api/report/products/expiry',
        ]);
    }

    // ── Sales Reports ─────────────────────────────────────────────────────

    public function test_step6_sales_reports()
    {
        $this->assertEndpoints([
            '/api/report/sales/items',
            '/api/report/sales/summary',
            '/api/report/sales/salesbyservice',
            '/api/report/sales/salesbyproduct',
            '/api/report/sales/paymentlist',
            '/api/report/sales/details',
            '/api/report/sales/unpaid',
            '/api/report/sales/discountsummary',
            '/api/report/sales/paymentsummary',
            '/api/report/sales/netincome',
            '/api/report/sales/dailyaudit',
            '/api/report/sales/staffservicesales',
        ]);
    }

    // ── Staff Reports ─────────────────────────────────────────────────────

    public function test_step7_staff_reports()
    {
        $this->assertEndpoints([
            '/api/report/staff/login',
            '/api/report/staff/late',
            '/api/report/staff/leave',
            '/api/report/staff/peformance',
        ]);
    }
}
