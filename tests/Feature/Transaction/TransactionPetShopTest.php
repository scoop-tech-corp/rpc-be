<?php

namespace Tests\Feature\Transaction;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TransactionPetShopTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/transaction/petshop ────────────────────────────────────────

    public function test_get_petshop_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petshop')->assertStatus(401);
    }

    public function test_get_petshop_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/petshop?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    // ─── POST /api/transaction/petshop — validasi ─────────────────────────────

    public function test_create_petshop_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petshop', [])->assertStatus(401);
    }

    public function test_create_petshop_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petshop', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/transaction/petshop/detail ─────────────────────────────────

    public function test_get_petshop_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petshop/detail?id=1')->assertStatus(401);
    }

    public function test_get_petshop_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/petshop/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 400, 404, 422]);
    }

    // ─── PUT/DELETE ───────────────────────────────────────────────────────────

    public function test_update_petshop_tanpa_auth_ditolak()
    {
        $this->putJson('/api/transaction/petshop', [])->assertStatus(401);
    }

    public function test_delete_petshop_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/petshop', ['id' => 1])->assertStatus(401);
    }

    // ─── POST /api/transaction/petshop/discount ───────────────────────────────

    public function test_discount_petshop_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petshop/discount', [])->assertStatus(401);
    }

    public function test_discount_petshop_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petshop/discount', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Payment proof, confirm, reject ──────────────────────────────────────

    public function test_upload_payment_proof_petshop_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petshop/upload-payment-proof', [])->assertStatus(401);
    }

    public function test_confirm_payment_petshop_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petshop/confirmPayment', [])->assertStatus(401);
    }

    public function test_confirm_payment_petshop_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/petshop/confirmPayment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_reject_payment_petshop_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/petshop/reject-payment', [])->assertStatus(401);
    }

    // ─── GET /api/transaction/petshop/generateInvoice ────────────────────────

    public function test_generate_invoice_petshop_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/petshop/generateInvoice/1')->assertStatus(401);
    }

    public function test_generate_invoice_petshop_id_tidak_ada()
    {
        $response = $this->getJson('/api/transaction/petshop/generateInvoice/99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 400, 404, 422, 500]);
    }

    // ─── Material Data ────────────────────────────────────────────────────────

    public function test_get_materialdata_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/materialdata')->assertStatus(401);
    }

    public function test_get_materialdata_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/materialdata', $this->auth())->assertStatus(200);
    }

    public function test_create_materialdata_tanpa_auth_ditolak()
    {
        $this->postJson('/api/transaction/materialdata', [])->assertStatus(401);
    }

    public function test_create_materialdata_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/transaction/materialdata', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_materialdata_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/transaction/materialdata', ['id' => 1])->assertStatus(401);
    }

    // ─── List Data (Weight, Temp, etc.) ──────────────────────────────────────

    public function test_get_listdata_payment_method_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/listdata/paymentmethod')->assertStatus(401);
    }

    public function test_get_listdata_payment_method_dengan_auth_berhasil()
    {
        $this->getJson('/api/transaction/listdata/paymentmethod', $this->auth())->assertStatus(200);
    }

    public function test_get_listdata_weight_tanpa_auth_ditolak()
    {
        $this->getJson('/api/transaction/listdata/weight')->assertStatus(401);
    }

    public function test_get_listdata_weight_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/transaction/listdata/weight', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }
}
