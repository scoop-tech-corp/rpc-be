<?php

namespace Tests\Feature\Transaction;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionPetHotelTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/transaction/pethotel ────────────────────────────────────────

    public function test_get_pethotel_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel')->assertStatus(401);
    }

    public function test_get_pethotel_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/pethotel?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_get_pethotel_stats_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/stats')->assertStatus(401);
    }

    public function test_get_pethotel_stats_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/pethotel/stats', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/transaction/pethotel — validasi ────────────────────────────

    public function test_create_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel', [])->assertStatus(401);
    }

    public function test_create_pethotel_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/pethotel', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/pethotel/detail ────────────────────────────────

    public function test_get_pethotel_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/detail?id=1')->assertStatus(401);
    }

    public function test_get_pethotel_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/pethotel/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── PUT/DELETE /api/transaction/pethotel ────────────────────────────────

    public function test_update_pethotel_tanpa_auth_ditolak()
    {
        $this->putJson('/api/transaction/pethotel', [])->assertStatus(401);
    }

    public function test_delete_pethotel_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/pethotel', ['id' => 1])->assertStatus(401);
    }

    // ─── POST /api/transaction/pethotel/accept ────────────────────────────────

    public function test_accept_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/accept', [])->assertStatus(401);
    }

    public function test_accept_pethotel_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/pethotel/accept', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/transaction/pethotel/petcheck ─────────────────────────────

    public function test_petcheck_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/petcheck', [])->assertStatus(401);
    }

    // ─── POST /api/transaction/pethotel/treatment ────────────────────────────

    public function test_treatment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/treatment', [])->assertStatus(401);
    }

    // ─── GET /api/transaction/pethotel/beforepayment ─────────────────────────

    public function test_before_payment_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/beforepayment?id=1')->assertStatus(401);
    }

    // ─── POST /api/transaction/pethotel/calculate ────────────────────────────

    public function test_calculate_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/calculate', [])->assertStatus(401);
    }

    public function test_calculate_pethotel_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/pethotel/calculate', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/pethotel/payment-methods ───────────────────────

    public function test_get_payment_methods_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/payment-methods')->assertStatus(401);
    }

    // ─── POST /api/transaction/pethotel/payment ───────────────────────────────

    public function test_payment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/payment', [])->assertStatus(401);
    }

    public function test_payment_pethotel_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/pethotel/payment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST/GET payment proof & confirm ─────────────────────────────────────

    public function test_upload_payment_proof_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/upload-payment-proof', [])->assertStatus(401);
    }

    public function test_confirm_payment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/confirm-payment', [])->assertStatus(401);
    }

    public function test_reject_payment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/reject-payment', [])->assertStatus(401);
    }

    // ─── Checkout ─────────────────────────────────────────────────────────────

    public function test_checkout_initiate_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/checkout/initiate', [])->assertStatus(401);
    }

    public function test_checkout_summary_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/checkout/summary?id=1')->assertStatus(401);
    }

    public function test_checkout_payment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/checkout/payment', [])->assertStatus(401);
    }

    // ─── Papan Kerja ──────────────────────────────────────────────────────────

    public function test_papan_kerja_harian_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/papan-kerja-harian')->assertStatus(401);
    }

    public function test_papan_kerja_harian_pethotel_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/transaction/pethotel/papan-kerja-harian', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── Additional Treatment ─────────────────────────────────────────────────

    public function test_get_additional_treatment_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/additional-treatment?id=1')->assertStatus(401);
    }

    public function test_add_additional_treatment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/additional-treatment', [])->assertStatus(401);
    }

    // ─── Prepayment ───────────────────────────────────────────────────────────

    public function test_get_prepayment_pethotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/pethotel/prepayment?id=1')->assertStatus(401);
    }

    public function test_add_prepayment_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/prepayment', [])->assertStatus(401);
    }

    // ─── Extend Stay ──────────────────────────────────────────────────────────

    public function test_extend_stay_pethotel_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/pethotel/extend-stay', [])->assertStatus(401);
    }
}
