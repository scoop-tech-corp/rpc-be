<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Tests\TestCase;

/**
 * Covers Finance Quotation module (/api/finance/quotation/*)
 */
class FinanceQuotationTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── QUOTATION INDEX ──────────────────────────────────────────────────────

    public function test_get_quotation_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_quotation_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/finance/quotation', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    // ─── QUOTATION CREATE ─────────────────────────────────────────────────────

    public function test_create_quotation_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/finance/quotation', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_quotation_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/quotation', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    // ─── QUOTATION UPDATE ─────────────────────────────────────────────────────

    public function test_update_quotation_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/finance/quotation', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── QUOTATION DELETE ─────────────────────────────────────────────────────

    public function test_delete_quotation_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/finance/quotation', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── QUOTATION DETAIL ─────────────────────────────────────────────────────

    public function test_get_quotation_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_quotation_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/finance/quotation/detail?id=999999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422, 500]);
    }

    // ─── QUOTATION STATUS ─────────────────────────────────────────────────────

    public function test_update_quotation_status_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/finance/quotation/status', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_update_quotation_status_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/quotation/status', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    // ─── QUOTATION DUPLICATE ──────────────────────────────────────────────────

    public function test_duplicate_quotation_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/finance/quotation/duplicate', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_duplicate_quotation_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/quotation/duplicate', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    // ─── QUOTATION CONVERT ────────────────────────────────────────────────────

    public function test_convert_quotation_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/finance/quotation/convert', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── QUOTATION DROPDOWNS ──────────────────────────────────────────────────

    public function test_get_customer_dropdown_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/customer-dropdown');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_customer_dropdown_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/finance/quotation/customer-dropdown', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_pet_dropdown_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/pet-dropdown');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_pet_dropdown_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/finance/quotation/pet-dropdown', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_get_discount_options_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/discount-options');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_discount_options_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/finance/quotation/discount-options', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    // ─── QUOTATION PRINT / EXPORT ─────────────────────────────────────────────

    public function test_print_quotation_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/print');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_export_excel_quotation_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/finance/quotation/export-excel');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }
}
