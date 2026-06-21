<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FinanceExtendedTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Finance Sales ────────────────────────────────────────────────────────

    public function test_finance_sales_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/sales')->assertStatus(401);
    }

    public function test_finance_sales_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/sales', $this->auth())->assertStatus(200);
    }

    public function test_finance_sales_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/sales/summary')->assertStatus(401);
    }

    public function test_finance_sales_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/sales/summary', $this->auth())->assertStatus(200);
    }

    public function test_finance_sales_payment_methods_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/sales/payment-methods')->assertStatus(401);
    }

    public function test_finance_sales_payment_methods_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/sales/payment-methods', $this->auth())->assertStatus(200);
    }

    public function test_finance_sales_payment_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/sales/payment-detail')->assertStatus(401);
    }

    public function test_finance_sales_add_payment_tanpa_auth_ditolak()
    {
        $this->postJson('/api/finance/sales/add-payment', [])->assertStatus(401);
    }

    public function test_finance_sales_add_payment_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/sales/add-payment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Finance Payment Records ──────────────────────────────────────────────

    public function test_finance_payment_records_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/payment-records')->assertStatus(401);
    }

    public function test_finance_payment_records_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/payment-records', $this->auth())->assertStatus(200);
    }

    public function test_finance_payment_records_payment_methods_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/payment-records/payment-methods')->assertStatus(401);
    }

    public function test_finance_payment_records_payment_methods_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/payment-records/payment-methods', $this->auth())->assertStatus(200);
    }

    // ─── Finance Piutang ──────────────────────────────────────────────────────

    public function test_finance_piutang_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/piutang')->assertStatus(401);
    }

    public function test_finance_piutang_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/piutang', $this->auth())->assertStatus(200);
    }

    public function test_finance_piutang_aging_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/piutang/aging-summary')->assertStatus(401);
    }

    public function test_finance_piutang_aging_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/piutang/aging-summary', $this->auth())->assertStatus(200);
    }

    // ─── Finance Refund ───────────────────────────────────────────────────────

    public function test_finance_refund_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/refund')->assertStatus(401);
    }

    public function test_finance_refund_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/refund', $this->auth())->assertStatus(200);
    }

    public function test_finance_refund_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/refund/summary')->assertStatus(401);
    }

    public function test_finance_refund_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/refund/summary', $this->auth())->assertStatus(200);
    }

    public function test_finance_refund_invoice_lookup_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/refund/invoice-lookup')->assertStatus(401);
    }

    public function test_finance_refund_payment_methods_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/refund/payment-methods')->assertStatus(401);
    }

    public function test_finance_refund_payment_methods_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/refund/payment-methods', $this->auth())->assertStatus(200);
    }

    public function test_create_refund_tanpa_auth_ditolak()
    {
        $this->postJson('/api/finance/refund', [])->assertStatus(401);
    }

    public function test_create_refund_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/refund', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_refund_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/finance/refund/1')->assertStatus(401);
    }

    public function test_delete_refund_id_tidak_ada()
    {
        $response = $this->deleteJson('/api/finance/refund/99999', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422]);
    }

    // ─── Installment ─────────────────────────────────────────────────────────

    public function test_installment_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/installment')->assertStatus(401);
    }

    public function test_installment_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/installment', $this->auth())->assertStatus(200);
    }

    public function test_installment_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/installment/detail')->assertStatus(401);
    }

    public function test_installment_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/installment/summary')->assertStatus(401);
    }

    public function test_installment_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/installment/summary', $this->auth())->assertStatus(200);
    }

    public function test_create_installment_tanpa_auth_ditolak()
    {
        $this->postJson('/api/installment', [])->assertStatus(401);
    }

    public function test_create_installment_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/installment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_installment_record_payment_tanpa_auth_ditolak()
    {
        $this->postJson('/api/installment/payment', [])->assertStatus(401);
    }

    public function test_installment_record_payment_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/installment/payment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_installment_late_fee_tanpa_auth_ditolak()
    {
        $this->getJson('/api/installment/late-fee')->assertStatus(401);
    }

    public function test_cancel_installment_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/installment', [])->assertStatus(401);
    }

    public function test_cancel_installment_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/installment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }
}
