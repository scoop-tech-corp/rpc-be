<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MenuManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Timekeeper ───────────────────────────────────────────────────────────

    public function test_get_timekeeper_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/timekeeper')->assertStatus(401);
    }

    public function test_get_timekeeper_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/timekeeper', $this->auth())->assertStatus(200);
    }

    public function test_create_timekeeper_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/timekeeper', [])->assertStatus(401);
    }

    public function test_create_timekeeper_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/menu/timekeeper', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_timekeeper_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/timekeeper', [])->assertStatus(401);
    }

    public function test_delete_timekeeper_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/timekeeper', [])->assertStatus(401);
    }

    // ─── Menu Group ───────────────────────────────────────────────────────────

    public function test_get_menu_group_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/list-menu-group')->assertStatus(401);
    }

    public function test_get_menu_group_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/list-menu-group', $this->auth())->assertStatus(200);
    }

    public function test_get_menu_group_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/menu-group')->assertStatus(401);
    }

    public function test_get_menu_group_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/menu-group', $this->auth())->assertStatus(200);
    }

    public function test_create_menu_group_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/menu-group', [])->assertStatus(401);
    }

    public function test_create_menu_group_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/menu/menu-group', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_menu_group_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/menu-group', [])->assertStatus(401);
    }

    public function test_delete_menu_group_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/menu-group', [])->assertStatus(401);
    }

    // ─── Last Order helpers ───────────────────────────────────────────────────

    public function test_get_last_order_menu_group_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/last-order-menu-group')->assertStatus(401);
    }

    public function test_get_last_order_menu_group_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/menu/last-order-menu-group', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── Child Menu Group ─────────────────────────────────────────────────────

    public function test_get_child_menu_group_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/list-child-menu-group')->assertStatus(401);
    }

    public function test_get_child_menu_group_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/menu/list-child-menu-group', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    public function test_get_child_menu_group_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/child-menu-group')->assertStatus(401);
    }

    public function test_get_child_menu_group_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/child-menu-group', $this->auth())->assertStatus(200);
    }

    public function test_create_child_menu_group_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/child-menu-group', [])->assertStatus(401);
    }

    public function test_create_child_menu_group_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/menu/child-menu-group', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_child_menu_group_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/child-menu-group', [])->assertStatus(401);
    }

    public function test_delete_child_menu_group_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/child-menu-group', [])->assertStatus(401);
    }

    // ─── Grand Child Menu Group ───────────────────────────────────────────────

    public function test_get_grand_child_menu_group_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/grand-child-menu-group')->assertStatus(401);
    }

    public function test_get_grand_child_menu_group_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/grand-child-menu-group', $this->auth())->assertStatus(200);
    }

    public function test_create_grand_child_menu_group_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/grand-child-menu-group', [])->assertStatus(401);
    }

    public function test_create_grand_child_menu_group_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/menu/grand-child-menu-group', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_grand_child_menu_group_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/grand-child-menu-group', [])->assertStatus(401);
    }

    public function test_delete_grand_child_menu_group_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/grand-child-menu-group', [])->assertStatus(401);
    }

    // ─── Menu Profile ─────────────────────────────────────────────────────────

    public function test_get_menu_profile_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/profile')->assertStatus(401);
    }

    public function test_get_menu_profile_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/profile', $this->auth())->assertStatus(200);
    }

    public function test_create_menu_profile_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/profile', [])->assertStatus(401);
    }

    public function test_update_menu_profile_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/profile', [])->assertStatus(401);
    }

    public function test_delete_menu_profile_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/profile', [])->assertStatus(401);
    }

    // ─── Menu Setting ─────────────────────────────────────────────────────────

    public function test_get_menu_setting_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/setting')->assertStatus(401);
    }

    public function test_get_menu_setting_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/setting', $this->auth())->assertStatus(200);
    }

    public function test_create_menu_setting_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/setting', [])->assertStatus(401);
    }

    public function test_update_menu_setting_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/setting', [])->assertStatus(401);
    }

    public function test_delete_menu_setting_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/setting', [])->assertStatus(401);
    }

    // ─── Menu Report ──────────────────────────────────────────────────────────

    public function test_get_menu_report_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/menu-report')->assertStatus(401);
    }

    public function test_get_menu_report_dengan_auth_berhasil()
    {
        $this->getJson('/api/menu/menu-report', $this->auth())->assertStatus(200);
    }

    public function test_create_menu_report_tanpa_auth_ditolak()
    {
        $this->postJson('/api/menu/menu-report', [])->assertStatus(401);
    }

    public function test_create_menu_report_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/menu/menu-report', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_get_menu_report_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/menu/menu-report/detail')->assertStatus(401);
    }

    public function test_update_menu_report_tanpa_auth_ditolak()
    {
        $this->putJson('/api/menu/menu-report', [])->assertStatus(401);
    }

    public function test_delete_menu_report_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/menu/menu-report', [])->assertStatus(401);
    }
}
