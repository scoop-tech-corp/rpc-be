<?php

namespace Tests\Feature\Transaction;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionBreedingTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/transaction/breeding ───────────────────────────────────────

    public function test_get_breeding_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding')->assertStatus(401);
    }

    public function test_get_breeding_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/breeding?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_get_breeding_stats_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/stats')->assertStatus(401);
    }

    public function test_get_breeding_stats_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/breeding/stats', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/transaction/breeding — validasi ────────────────────────────

    public function test_create_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding', [])->assertStatus(401);
    }

    public function test_create_breeding_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/breeding', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/breeding/detail ────────────────────────────────

    public function test_get_breeding_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/detail?id=1')->assertStatus(401);
    }

    public function test_get_breeding_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/breeding/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── PUT/DELETE ───────────────────────────────────────────────────────────

    public function test_update_breeding_tanpa_auth_ditolak()
    {
        $this->putJson('/api/transaction/breeding', [])->assertStatus(401);
    }

    public function test_delete_breeding_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/breeding', ['id' => 1])->assertStatus(401);
    }

    // ─── POST accept, petcheck, treatment ────────────────────────────────────

    public function test_accept_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/accept', [])->assertStatus(401);
    }

    public function test_petcheck_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/petcheck', [])->assertStatus(401);
    }

    public function test_treatment_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/treatment', [])->assertStatus(401);
    }

    // ─── Payment ──────────────────────────────────────────────────────────────

    public function test_get_before_payment_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/beforepayment?id=1')->assertStatus(401);
    }

    public function test_payment_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/payment', [])->assertStatus(401);
    }

    public function test_payment_breeding_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/breeding/payment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_upload_payment_proof_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/upload-payment-proof', [])->assertStatus(401);
    }

    public function test_confirm_payment_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/confirm-payment', [])->assertStatus(401);
    }

    public function test_reject_payment_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/reject-payment', [])->assertStatus(401);
    }

    // ─── Checkout ─────────────────────────────────────────────────────────────

    public function test_checkout_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/checkout', [])->assertStatus(401);
    }

    public function test_checkout_summary_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/checkout/summary?id=1')->assertStatus(401);
    }

    // ─── Prepayment ───────────────────────────────────────────────────────────

    public function test_get_prepayments_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/prepayments?id=1')->assertStatus(401);
    }

    public function test_add_prepayment_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/prepayments', [])->assertStatus(401);
    }

    // ─── Papan Kerja ──────────────────────────────────────────────────────────

    public function test_get_papan_kerja_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/papan-kerja')->assertStatus(401);
    }

    // ─── Policies ─────────────────────────────────────────────────────────────

    public function test_get_policies_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/breeding/policies')->assertStatus(401);
    }

    public function test_save_policy_agreement_breeding_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/breeding/policy-agreement', [])->assertStatus(401);
    }
}
