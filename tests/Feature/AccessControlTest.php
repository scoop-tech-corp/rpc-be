<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccessControlTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/accesscontrol ───────────────────────────────────────────────

    public function test_get_access_control_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol')->assertStatus(401);
    }

    public function test_get_access_control_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/accesscontrol/user ─────────────────────────────────────────

    public function test_get_access_control_user_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/user')->assertStatus(401);
    }

    public function test_get_access_control_user_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/user', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/accesscontrol/history ──────────────────────────────────────

    public function test_get_access_control_history_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/history')->assertStatus(401);
    }

    public function test_get_access_control_history_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/history', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/accesscontrol/accesstype ───────────────────────────────────

    public function test_get_access_type_dropdown_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/accesstype')->assertStatus(401);
    }

    public function test_get_access_type_dropdown_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/accesstype', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/accesscontrol/menumaster ───────────────────────────────────

    public function test_get_menumaster_dropdown_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/menumaster')->assertStatus(401);
    }

    public function test_get_menumaster_dropdown_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/menumaster', $this->auth())->assertStatus(200);
    }

    public function test_get_menumaster_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/menumaster/index')->assertStatus(401);
    }

    public function test_get_menumaster_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/menumaster/index', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/accesscontrol/menulist ─────────────────────────────────────

    public function test_get_menulist_dropdown_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/menulist')->assertStatus(401);
    }

    public function test_get_menulist_dropdown_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/menulist', $this->auth())->assertStatus(200);
    }

    public function test_get_menulist_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/accesscontrol/menulist/index')->assertStatus(401);
    }

    public function test_get_menulist_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/accesscontrol/menulist/index', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/accesscontrol/menulist ────────────────────────────────────

    public function test_insert_menulist_tanpa_auth_ditolak()
    {
        $this->postJson('/api/accesscontrol/menulist', [])->assertStatus(401);
    }

    public function test_insert_menulist_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/accesscontrol/menulist', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/accesscontrol/menumaster ──────────────────────────────────

    public function test_insert_menumaster_tanpa_auth_ditolak()
    {
        $this->postJson('/api/accesscontrol/menumaster', [])->assertStatus(401);
    }

    public function test_insert_menumaster_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/accesscontrol/menumaster', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── PUT endpoints ────────────────────────────────────────────────────────

    public function test_update_access_control_menu_tanpa_auth_ditolak()
    {
        $this->putJson('/api/accesscontrol/menu', [])->assertStatus(401);
    }

    public function test_update_access_control_menu_gagal_tanpa_required_fields()
    {
        $response = $this->putJson('/api/accesscontrol/menu', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_menulist_tanpa_auth_ditolak()
    {
        $this->putJson('/api/accesscontrol/menulist', [])->assertStatus(401);
    }

    public function test_update_menumaster_tanpa_auth_ditolak()
    {
        $this->putJson('/api/accesscontrol/menumaster', [])->assertStatus(401);
    }

    // ─── DELETE /api/accesscontrol/menu ──────────────────────────────────────

    public function test_delete_access_control_menu_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/accesscontrol/menu', [])->assertStatus(401);
    }

    public function test_delete_access_control_menu_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/accesscontrol/menu', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }
}
