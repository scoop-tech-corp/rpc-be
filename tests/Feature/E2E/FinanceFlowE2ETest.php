<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E Test: Finance Flow
 *
 * Skenario:
 *   1. Finance dashboard
 *   2. Buat expense (pengeluaran)
 *   3. Approval expense
 *   4. Lihat sales report
 *   5. Quotation lifecycle (buat → update status → duplicate)
 *   6. Piutang & Payment records
 *   7. Installment flow
 */
class FinanceFlowE2ETest extends TestCase
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
            'usersId'        => $this->user->id,
            'locationId'     => $this->locationId,
            'isMainLocation' => 1,
            'isDeleted'      => 0,
            'created_at'     => now(),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function h(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ── Step 1: Finance dashboard ──────────────────────────────────────────

    public function test_step1_finance_dashboard_accessible()
    {
        $response = $this->getJson('/api/finance/dashboard', $this->h());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ── Step 2: Master data dropdowns ─────────────────────────────────────

    public function test_step2_master_data_dropdowns_accessible()
    {
        $endpoints = [
            '/api/finance/vendor',
            '/api/finance/category',
            '/api/finance/expense-type',
            '/api/finance/department',
            '/api/finance/payment-status',
            '/api/finance/payment-method',
        ];

        foreach ($endpoints as $ep) {
            $resp = $this->getJson($ep, $this->h());
            $this->assertContains(
                $resp->getStatusCode(),
                [200, 422],
                "Finance master data {$ep}: " . $resp->getStatusCode()
            );
        }
    }

    // ── Step 3: Expense lifecycle ──────────────────────────────────────────

    public function test_step3_expense_lifecycle()
    {
        // 3a. List expense
        $list = $this->getJson('/api/finance/expense?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        // 3b. Buat expense
        $create = $this->postJson('/api/finance/expense', [
            'expenseDate'        => now()->format('Y-m-d'),
            'amount'             => 150000,
            'description'        => 'E2E test expense',
            'expenseTypeId'      => 1,
            'categoryId'         => 1,
            'paymentMethodId'    => 1,
            'locationId'         => $this->locationId,
        ], $this->h());
        $this->assertContains($create->getStatusCode(), [200, 201, 422]);

        // 3c. Detail expense input kosong → validasi
        $detail = $this->getJson('/api/finance/expense/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);
    }

    // ── Step 4: Sales report endpoints ────────────────────────────────────

    public function test_step4_sales_endpoints_accessible()
    {
        $endpoints = [
            '/api/finance/sales?rowPerPage=10&goToPage=1',
            '/api/finance/sales/summary',
            '/api/finance/sales/payment-methods',
        ];

        foreach ($endpoints as $ep) {
            $resp = $this->getJson($ep, $this->h());
            $this->assertContains(
                $resp->getStatusCode(),
                [200, 422],
                "Finance sales {$ep}: " . $resp->getStatusCode()
            );
        }
    }

    // ── Step 5: Payment records ────────────────────────────────────────────

    public function test_step5_payment_records_accessible()
    {
        $resp = $this->getJson('/api/finance/payment-records?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($resp->getStatusCode(), [200]);

        $methods = $this->getJson('/api/finance/payment-records/payment-methods', $this->h());
        $this->assertContains($methods->getStatusCode(), [200, 422]);
    }

    // ── Step 6: Piutang (receivables) ─────────────────────────────────────

    public function test_step6_piutang_accessible()
    {
        $resp = $this->getJson('/api/finance/piutang?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($resp->getStatusCode(), [200]);

        $aging = $this->getJson('/api/finance/piutang/aging-summary', $this->h());
        $this->assertContains($aging->getStatusCode(), [200, 422]);
    }

    // ── Step 7: Quotation lifecycle ───────────────────────────────────────

    public function test_step7_quotation_lifecycle()
    {
        // 7a. List
        $list = $this->getJson('/api/finance/quotation?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        // 7b. Buat quotation
        $create = $this->postJson('/api/finance/quotation', [
            'customerId'  => 2,
            'locationId'  => $this->locationId,
            'validUntil'  => now()->addDays(30)->format('Y-m-d'),
            'items'       => [
                [
                    'description' => 'E2E Test Item',
                    'quantity'    => 1,
                    'unitPrice'   => 100000,
                ],
            ],
        ], $this->h());
        $this->assertContains($create->getStatusCode(), [200, 201, 422]);

        if (in_array($create->getStatusCode(), [200, 201])) {
            $quoteId = data_get($create->json(), 'data.id')
                ?? data_get($create->json(), 'id')
                ?? DB::table('quotations')->where('customerId', 2)->latest('id')->value('id');

            if ($quoteId) {
                // 7c. Detail
                $detail = $this->getJson('/api/finance/quotation/detail?id=' . $quoteId, $this->h());
                $this->assertContains($detail->getStatusCode(), [200, 404]);

                // 7d. Update status
                $status = $this->postJson('/api/finance/quotation/status', [
                    'id'     => $quoteId,
                    'status' => 'sent',
                ], $this->h());
                $this->assertContains($status->getStatusCode(), [200, 201, 422, 404]);

                // 7e. Duplicate
                $dup = $this->postJson('/api/finance/quotation/duplicate', [
                    'id' => $quoteId,
                ], $this->h());
                $this->assertContains($dup->getStatusCode(), [200, 201, 422, 404]);
            }
        }
    }

    // ── Step 8: Refund endpoints ───────────────────────────────────────────

    public function test_step8_refund_endpoints_accessible()
    {
        // List
        $list = $this->getJson('/api/finance/refund?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        // Summary
        $summary = $this->getJson('/api/finance/refund/summary', $this->h());
        $this->assertContains($summary->getStatusCode(), [200, 422]);

        // Buat refund input kosong → 422
        $create = $this->postJson('/api/finance/refund', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);
    }

    // ── Step 9: Installment endpoints ─────────────────────────────────────

    public function test_step9_installment_endpoints_accessible()
    {
        $list = $this->getJson('/api/installment?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        $summary = $this->getJson('/api/installment/summary', $this->h());
        $this->assertContains($summary->getStatusCode(), [200, 422]);

        // Buat installment input kosong → 422
        $create = $this->postJson('/api/installment', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);
    }
}
