<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FinanceTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_finance_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/dashboard')->assertStatus(401);
    }

    public function test_finance_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/dashboard', $this->auth())->assertStatus(200);
    }

    // ─── Master Data ──────────────────────────────────────────────────────────

    public function test_get_vendor_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/vendor')->assertStatus(401);
    }

    public function test_get_vendor_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/vendor', $this->auth())->assertStatus(200);
    }

    public function test_create_vendor_tanpa_auth_ditolak()
    {
        $this->postJson('/api/finance/vendor', [])->assertStatus(401);
    }

    public function test_create_vendor_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/vendor', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_get_category_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/category')->assertStatus(401);
    }

    public function test_get_category_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/category', $this->auth())->assertStatus(200);
    }

    public function test_get_expense_type_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/expense-type')->assertStatus(401);
    }

    public function test_get_expense_type_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/expense-type', $this->auth())->assertStatus(200);
    }

    public function test_get_department_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/department')->assertStatus(401);
    }

    public function test_get_department_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/department', $this->auth())->assertStatus(200);
    }

    public function test_get_payment_method_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/payment-method')->assertStatus(401);
    }

    public function test_get_payment_method_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/payment-method', $this->auth())->assertStatus(200);
    }

    public function test_get_payment_status_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/payment-status')->assertStatus(401);
    }

    public function test_get_payment_status_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/payment-status', $this->auth())->assertStatus(200);
    }

    // ─── Expense ──────────────────────────────────────────────────────────────

    public function test_get_expense_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/expense')->assertStatus(401);
    }

    public function test_get_expense_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/expense?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_expense_tanpa_auth_ditolak()
    {
        $this->postJson('/api/finance/expense', [])->assertStatus(401);
    }

    public function test_create_expense_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/expense', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_expense_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/finance/expense', ['id' => 1])->assertStatus(401);
    }

    // ─── Quotation ────────────────────────────────────────────────────────────

    public function test_get_quotation_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/quotation')->assertStatus(401);
    }

    public function test_get_quotation_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/quotation?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_quotation_tanpa_auth_ditolak()
    {
        $this->postJson('/api/finance/quotation', [])->assertStatus(401);
    }

    public function test_create_quotation_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/finance/quotation', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_quotation_tanpa_auth_ditolak()
    {
        $this->putJson('/api/finance/quotation', [])->assertStatus(401);
    }

    public function test_delete_quotation_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/finance/quotation', ['id' => 1])->assertStatus(401);
    }

    // ─── Data Static ─────────────────────────────────────────────────────────

    public function test_get_finance_datastatic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/finance/data-static')->assertStatus(401);
    }

    public function test_get_finance_datastatic_dengan_auth_berhasil()
    {
        $this->getJson('/api/finance/data-static', $this->auth())->assertStatus(200);
    }
}
