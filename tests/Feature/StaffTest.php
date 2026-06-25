<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StaffTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_staff_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/dashboard')->assertStatus(401);
    }

    public function test_staff_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/staff/dashboard', $this->auth())->assertStatus(200);
    }

    // ─── Staff List ───────────────────────────────────────────────────────────

    public function test_get_staff_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff')->assertStatus(401);
    }

    public function test_get_staff_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/staff?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_staff_tanpa_auth_ditolak()
    {
        $this->postJson('/api/staff', [])->assertStatus(401);
    }

    public function test_create_staff_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_staff_tanpa_auth_ditolak()
    {
        $this->putJson('/api/staff', [])->assertStatus(401);
    }

    public function test_delete_staff_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/staff', ['id' => 1])->assertStatus(401);
    }

    public function test_get_staff_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/staffdetail?id=1')->assertStatus(401);
    }

    // ─── Job Title ───────────────────────────────────────────────────────────

    public function test_get_jobtitle_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/jobtitle')->assertStatus(401);
    }

    public function test_get_jobtitle_dengan_auth_berhasil()
    {
        $this->getJson('/api/staff/jobtitle', $this->auth())->assertStatus(200);
    }

    public function test_create_jobtitle_tanpa_auth_ditolak()
    {
        $this->postJson('/api/staff/jobtitle', [])->assertStatus(401);
    }

    public function test_create_jobtitle_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/jobtitle', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Role / Roles ────────────────────────────────────────────────────────

    public function test_get_role_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/rolesid')->assertStatus(401);
    }

    public function test_get_role_dengan_auth_berhasil()
    {
        $this->getJson('/api/staff/rolesid', $this->auth())->assertStatus(200);
    }

    // ─── Pay Period ──────────────────────────────────────────────────────────

    public function test_get_pay_period_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/payperiod')->assertStatus(401);
    }

    public function test_get_pay_period_dengan_auth_berhasil()
    {
        $this->getJson('/api/staff/payperiod', $this->auth())->assertStatus(200);
    }

    // ─── Staff Leave ─────────────────────────────────────────────────────────

    public function test_get_staff_leave_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/leave')->assertStatus(401);
    }

    public function test_create_staff_leave_tanpa_auth_ditolak()
    {
        $this->postJson('/api/staff/leave', [])->assertStatus(401);
    }

    public function test_create_staff_leave_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/leave', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Staff Profile ────────────────────────────────────────────────────────

    public function test_get_staff_profile_tanpa_auth_ditolak()
    {
        $this->getJson('/api/staff/profile')->assertStatus(401);
    }
}
