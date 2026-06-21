<?php

namespace Tests\Feature\Transaction;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionPetClinicTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard & Category ─────────────────────────────────────────────────

    public function test_transaction_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/dashboard')->assertStatus(401);
    }

    public function test_transaction_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/dashboard', $this->auth())->assertStatus(200);
    }

    public function test_transaction_category_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/category')->assertStatus(401);
    }

    public function test_transaction_category_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/category', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/transaction/petclinic ──────────────────────────────────────

    public function test_get_petclinic_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petclinic')->assertStatus(401);
    }

    public function test_get_petclinic_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/petclinic?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_get_petclinic_stats_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petclinic/stats')->assertStatus(401);
    }

    public function test_get_petclinic_stats_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/petclinic/stats', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/transaction/petclinic — validasi ───────────────────────────

    public function test_create_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic', [])->assertStatus(401);
    }

    public function test_create_petclinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petclinic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/petclinic/detail ────────────────────────────────

    public function test_get_petclinic_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petclinic/detail?id=1')->assertStatus(401);
    }

    public function test_get_petclinic_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/petclinic/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── PUT /api/transaction/petclinic ──────────────────────────────────────

    public function test_update_petclinic_tanpa_auth_ditolak()
    {
        $this->putJson('/api/transaction/petclinic', [])->assertStatus(401);
    }

    // ─── DELETE /api/transaction/petclinic ───────────────────────────────────

    public function test_delete_petclinic_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/petclinic', ['id' => 1])->assertStatus(401);
    }

    // ─── POST /api/transaction/petclinic/accept ───────────────────────────────

    public function test_accept_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/accept', [])->assertStatus(401);
    }

    public function test_accept_petclinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petclinic/accept', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/transaction/petclinic/petcheck ─────────────────────────────

    public function test_petcheck_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/petcheck', [])->assertStatus(401);
    }

    public function test_petcheck_petclinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petclinic/petcheck', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/petclinic/beforepayment ────────────────────────

    public function test_before_payment_petclinic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petclinic/beforepayment?id=1')->assertStatus(401);
    }

    // ─── POST /api/transaction/petclinic/calculate ───────────────────────────

    public function test_calculate_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/calculate', [])->assertStatus(401);
    }

    public function test_calculate_petclinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petclinic/calculate', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/petclinic/payment-methods ──────────────────────

    public function test_get_payment_methods_petclinic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petclinic/payment-methods')->assertStatus(401);
    }

    public function test_get_payment_methods_petclinic_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/transaction/petclinic/payment-methods', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── POST /api/transaction/petclinic/payment/outpatient ──────────────────

    public function test_payment_outpatient_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/payment/outpatient', [])->assertStatus(401);
    }

    public function test_payment_outpatient_petclinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petclinic/payment/outpatient', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/transaction/petclinic/upload-payment-proof ────────────────

    public function test_upload_payment_proof_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/upload-payment-proof', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/petclinic/confirm-payment ─────────────────────

    public function test_confirm_payment_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/confirm-payment', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/petclinic/reject-payment ──────────────────────

    public function test_reject_payment_petclinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petclinic/reject-payment', [])->assertStatus(401);
    }
}
