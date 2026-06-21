<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AbsentTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── POST /api/absent ─────────────────────────────────────────────────────

    public function test_create_absent_tanpa_auth_ditolak()
    {
        $this->postJson('/api/absent', [])->assertStatus(401);
    }

    public function test_create_absent_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/absent', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/absent/staff-list ───────────────────────────────────────────

    public function test_get_staff_list_absent_tanpa_auth_ditolak()
    {
        $this->getJson('/api/absent/staff-list')->assertStatus(401);
    }

    public function test_get_staff_list_absent_dengan_auth_berhasil()
    {
        $this->getJson('/api/absent/staff-list', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/absent/index ────────────────────────────────────────────────

    public function test_get_absent_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/absent/index')->assertStatus(401);
    }

    public function test_get_absent_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/absent/index', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/absent/present-list ────────────────────────────────────────

    public function test_get_present_status_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/absent/present-list')->assertStatus(401);
    }

    public function test_get_present_status_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/absent/present-list', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/absent/detail ───────────────────────────────────────────────

    public function test_get_absent_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/absent/detail?id=1')->assertStatus(401);
    }

    public function test_get_absent_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/absent/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422]);
    }

    // ─── GET /api/absent/export ───────────────────────────────────────────────

    public function test_get_absent_export_tanpa_auth_ditolak()
    {
        $this->getJson('/api/absent/export')->assertStatus(401);
    }
}
