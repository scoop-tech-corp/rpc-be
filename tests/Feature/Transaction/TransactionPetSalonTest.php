<?php

namespace Tests\Feature\Transaction;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionPetSalonTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/transaction/petsalon ────────────────────────────────────────

    public function test_get_petsalon_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petsalon')->assertStatus(401);
    }

    public function test_get_petsalon_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/petsalon?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_get_petsalon_stats_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petsalon/stats')->assertStatus(401);
    }

    public function test_get_petsalon_stats_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/petsalon/stats', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/transaction/petsalon — validasi ────────────────────────────

    public function test_create_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon', [])->assertStatus(401);
    }

    public function test_create_petsalon_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petsalon', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/petsalon/detail ────────────────────────────────

    public function test_get_petsalon_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petsalon/detail?id=1')->assertStatus(401);
    }

    public function test_get_petsalon_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/petsalon/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── PUT/DELETE ───────────────────────────────────────────────────────────

    public function test_update_petsalon_tanpa_auth_ditolak()
    {
        $this->putJson('/api/transaction/petsalon', [])->assertStatus(401);
    }

    public function test_delete_petsalon_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/petsalon', ['id' => 1])->assertStatus(401);
    }

    // ─── POST /api/transaction/petsalon/accept ────────────────────────────────

    public function test_accept_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/accept', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/petsalon/petcheck ─────────────────────────────

    public function test_petcheck_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/petcheck', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/petsalon/treatment ────────────────────────────

    public function test_treatment_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/treatment', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/petsalon/salon-done ───────────────────────────

    public function test_salon_done_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/salon-done', [])->assertStatus(401);
    }

    public function test_salon_done_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petsalon/salon-done', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/transaction/petsalon/checkout ──────────────────────────────

    public function test_checkout_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/checkout', [])->assertStatus(401);
    }

    // ─── GET /api/transaction/petsalon/beforepayment ─────────────────────────

    public function test_before_payment_petsalon_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petsalon/beforepayment?id=1')->assertStatus(401);
    }

    // ─── POST /api/transaction/petsalon/payment ───────────────────────────────

    public function test_payment_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/payment', [])->assertStatus(401);
    }

    public function test_payment_petsalon_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petsalon/payment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Payment proof, confirm, reject ──────────────────────────────────────

    public function test_upload_payment_proof_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/upload-payment-proof', [])->assertStatus(401);
    }

    public function test_confirm_payment_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/confirm-payment', [])->assertStatus(401);
    }

    public function test_reject_payment_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/reject-payment', [])->assertStatus(401);
    }

    // ─── Policies ─────────────────────────────────────────────────────────────

    public function test_get_policies_petsalon_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petsalon/policies')->assertStatus(401);
    }

    public function test_save_policy_agreement_petsalon_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petsalon/policy-agreement', [])->assertStatus(401);
    }
}
