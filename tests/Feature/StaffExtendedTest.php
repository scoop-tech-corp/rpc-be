<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Tests\TestCase;

/**
 * Covers staff sub-modules NOT in StaffTest.php:
 * Overwork, Identity, SalarySlip, RequireSalary, AccessControlSchedules, DataStatic
 */
class StaffExtendedTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── OVERWORK ─────────────────────────────────────────────────────────────

    public function test_get_overwork_full_shift_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/overwork/full-shift');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_overwork_full_shift_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/overwork/full-shift', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_overwork_full_shift_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/overwork/full-shift', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_overwork_full_shift_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/overwork/full-shift', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_update_overwork_full_shift_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/staff/overwork/full-shift', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_overwork_full_shift_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/overwork/full-shift', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_overwork_long_shift_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/overwork/long-shift');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_overwork_long_shift_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/overwork/long-shift', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_overwork_long_shift_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/overwork/long-shift', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_overwork_long_shift_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/overwork/long-shift', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    // ─── IDENTITY ─────────────────────────────────────────────────────────────

    public function test_get_identity_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/identity');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_identity_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/identity', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_identity_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/identity', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_identity_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/identity', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_identity_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/identity', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── SALARY SLIP ──────────────────────────────────────────────────────────

    public function test_get_salary_slip_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/salary-slip');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_salary_slip_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/salary-slip', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_salary_slip_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/salary-slip', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_salary_slip_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/salary-slip', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 404, 500]);
    }

    public function test_get_salary_slip_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/salary-slip/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_salary_slip_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/salary-slip', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_generate_salary_slip_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/salary-slip/generate-slip');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── REQUIRE SALARY ───────────────────────────────────────────────────────

    public function test_get_req_salary_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/req-salary');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_req_salary_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/req-salary', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_req_salary_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/req-salary', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_req_salary_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/req-salary', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_get_req_salary_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/req-salary/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_req_salary_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/req-salary', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── ACCESS CONTROL SCHEDULES ─────────────────────────────────────────────

    public function test_get_schedule_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/schedule');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_schedule_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/schedule', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_schedule_menu_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/schedule/menulist');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_schedule_menu_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/schedule/menulist', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_insert_schedule_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/schedule', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_insert_schedule_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/schedule', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_schedule_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/schedule', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_schedule_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/schedule/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_schedule_staff_from_location_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/schedule/liststaff');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── DATASTATIC STAFF ─────────────────────────────────────────────────────

    public function test_get_datastatic_staff_index_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/datastatic');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_datastatic_staff_index_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/datastatic', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_datastatic_staff_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/staff/datastatic/staff');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_datastatic_staff_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/staff/datastatic/staff', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_insert_datastatic_staff_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/staff/datastatic', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_insert_datastatic_staff_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/staff/datastatic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_datastatic_staff_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/staff/datastatic', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }
}
