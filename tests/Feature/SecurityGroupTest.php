<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SecurityGroupTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/securitygroup ───────────────────────────────────────────────

    public function test_get_security_group_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/securitygroup')->assertStatus(401);
    }

    public function test_get_security_group_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/securitygroup', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/securitygroup/detail ───────────────────────────────────────

    public function test_get_security_group_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/securitygroup/detail?id=1')->assertStatus(401);
    }

    public function test_get_security_group_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/securitygroup/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422]);
    }

    // ─── GET /api/securitygroup/users ────────────────────────────────────────

    public function test_get_security_group_users_dropdown_tanpa_auth_ditolak()
    {
        $this->getJson('/api/securitygroup/users')->assertStatus(401);
    }

    public function test_get_security_group_users_dropdown_dengan_auth_berhasil()
    {
        $this->getJson('/api/securitygroup/users', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/securitygroup ──────────────────────────────────────────────

    public function test_create_security_group_tanpa_auth_ditolak()
    {
        $this->postJson('/api/securitygroup', [])->assertStatus(401);
    }

    public function test_create_security_group_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/securitygroup', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── PUT /api/securitygroup ───────────────────────────────────────────────

    public function test_update_security_group_tanpa_auth_ditolak()
    {
        $this->putJson('/api/securitygroup', [])->assertStatus(401);
    }

    public function test_update_security_group_gagal_tanpa_required_fields()
    {
        $response = $this->putJson('/api/securitygroup', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── DELETE /api/securitygroup ────────────────────────────────────────────

    public function test_delete_security_group_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/securitygroup', [])->assertStatus(401);
    }

    public function test_delete_security_group_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/securitygroup', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }
}
