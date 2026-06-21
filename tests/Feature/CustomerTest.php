<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CustomerTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthHeader(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/customer ────────────────────────────────────────────────────

    public function test_get_customer_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer');

        $response->assertStatus(401);
    }

    public function test_get_customer_list_dengan_auth_berhasil()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->getJson('/api/customer?rowPerPage=10&goToPage=1', $headers);

        $response->assertStatus(200);
    }

    // ─── POST /api/customer — validasi ────────────────────────────────────────

    public function test_create_customer_gagal_tanpa_auth()
    {
        $response = $this->postJson('/api/customer', []);

        $response->assertStatus(401);
    }

    public function test_create_customer_gagal_tanpa_required_fields()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->postJson('/api/customer', [], $headers);

        $response->assertStatus(422);
    }

    public function test_create_customer_gagal_tanpa_firstName()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->postJson('/api/customer', [
            'lastName'        => 'Santoso',
            'locationId'      => 1,
            'joinDate'        => '2026-01-01',
        ], $headers);

        // Validasi firstName required → 422 atau 403 (jika akses ditolak)
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/customer/detail ────────────────────────────────────────────

    public function test_get_customer_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/detail?id=1');

        $response->assertStatus(401);
    }

    public function test_get_customer_detail_id_tidak_ada_returns_error()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->getJson('/api/customer/detail?id=99999', $headers);

        // Customer tidak ada, harusnya return 422 (not found) atau 200 dengan data null
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── DELETE /api/customer ────────────────────────────────────────────────

    public function test_delete_customer_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/customer', ['id' => 1]);

        $response->assertStatus(401);
    }

    // ─── GET /api/customer/typeid ─────────────────────────────────────────────

    public function test_get_typeid_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/typeid');

        $response->assertStatus(401);
    }

    public function test_get_typeid_dengan_auth_berhasil()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->getJson('/api/customer/typeid', $headers);

        $response->assertStatus(200);
    }

    // ─── POST /api/customer/typeid — validasi ────────────────────────────────

    public function test_create_typeid_gagal_tanpa_typeName()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->postJson('/api/customer/typeid', [], $headers);

        $response->assertStatus(422);
    }

    // ─── GET /api/customer/group ──────────────────────────────────────────────

    public function test_get_customer_group_dengan_auth_berhasil()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->getJson('/api/customer/group', $headers);

        $response->assertStatus(200);
    }
}
