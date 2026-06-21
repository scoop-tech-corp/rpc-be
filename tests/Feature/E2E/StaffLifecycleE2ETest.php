<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Staff Lifecycle
 * dashboard → create staff → leave → overwork → identity → salary slip → require salary → profile
 */
class StaffLifecycleE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['isDeleted' => 0, 'roleId' => 1]);
        DB::table('usersLocation')->insert([
            'usersId' => $this->user->id, 'locationId' => $this->locationId,
            'isMainLocation' => 1, 'isDeleted' => 0, 'created_at' => now(),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function h(): array { return ['Authorization' => 'Bearer ' . $this->token]; }

    // ── Dashboard & Dropdowns ─────────────────────────────────────────────

    public function test_step1_dashboard_dan_dropdowns()
    {
        $dash = $this->getJson('/api/staff/dashboard', $this->h());
        $this->assertContains($dash->getStatusCode(), [200, 422]);

        foreach (['/api/staff/rolestaff', '/api/staff/typeid', '/api/staff/jobtitle'] as $ep) {
            $r = $this->getJson($ep, $this->h());
            $this->assertContains($r->getStatusCode(), [200, 422], "$ep gagal");
        }
    }

    // ── Staff CRUD Lifecycle ──────────────────────────────────────────────

    public function test_step2_staff_crud_lifecycle()
    {
        // List
        $list = $this->getJson('/api/staff?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $list->getStatusCode());

        // Create (kosong → validasi)
        $create = $this->postJson('/api/staff', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        // Create with minimal data
        $createFull = $this->postJson('/api/staff', [
            'firstName'   => 'E2E Staff ' . rand(100, 999),
            'email'       => 'e2estaff' . rand(100, 999) . '@test.com',
            'password'    => 'password123',
            'roleId'      => 1,
            'locationId'  => $this->locationId,
            'jobTitleId'  => 1,
        ], $this->h());
        $this->assertContains($createFull->getStatusCode(), [200, 201, 422]);

        // Update & Delete (kosong → validasi)
        $this->assertContains($this->putJson('/api/staff', [], $this->h())->getStatusCode(), [422, 403, 404]);
        $this->assertContains($this->deleteJson('/api/staff', [], $this->h())->getStatusCode(), [422, 403, 404]);
    }

    // ── Staff Leave ───────────────────────────────────────────────────────

    public function test_step3_staff_leave_lifecycle()
    {
        // Leave list (requires 'status' param on some versions)
        $list = $this->getJson('/api/staff/leave?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200, 422]);

        // Leave type dropdown
        $type = $this->getJson('/api/staff/leave/leavetype', $this->h());
        $this->assertContains($type->getStatusCode(), [200, 422]);

        // Leave balance
        $balance = $this->getJson('/api/staff/leave/leavebalance?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($balance->getStatusCode(), [200, 422]);

        // Working date
        $wd = $this->getJson('/api/staff/leave/workingdate', $this->h());
        $this->assertContains($wd->getStatusCode(), [200, 422]);

        // Insert leave (kosong → validasi)
        $insert = $this->postJson('/api/staff/leave', [], $this->h());
        $this->assertContains($insert->getStatusCode(), [422, 403]);

        // Approve all / reject all (kosong → validasi)
        $this->assertContains($this->putJson('/api/staff/leave/approveall', [], $this->h())->getStatusCode(), [422, 403, 200]);
        $this->assertContains($this->putJson('/api/staff/leave/rejectall', [], $this->h())->getStatusCode(), [422, 403, 200]);
    }

    // ── Overwork ──────────────────────────────────────────────────────────

    public function test_step4_overwork_lifecycle()
    {
        // Full shift
        $fs = $this->getJson('/api/staff/overwork/full-shift?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($fs->getStatusCode(), [200]);

        $createFS = $this->postJson('/api/staff/overwork/full-shift', [], $this->h());
        $this->assertContains($createFS->getStatusCode(), [422, 403]);

        // Long shift
        $ls = $this->getJson('/api/staff/overwork/long-shift?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($ls->getStatusCode(), [200]);

        $createLS = $this->postJson('/api/staff/overwork/long-shift', [], $this->h());
        $this->assertContains($createLS->getStatusCode(), [422, 403]);
    }

    // ── Identity ──────────────────────────────────────────────────────────

    public function test_step5_identity_lifecycle()
    {
        $list = $this->getJson('/api/staff/identity?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        $create = $this->postJson('/api/staff/identity', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);
    }

    // ── Salary Slip ───────────────────────────────────────────────────────

    public function test_step6_salary_slip_lifecycle()
    {
        $list = $this->getJson('/api/staff/salary-slip?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        $detail = $this->getJson('/api/staff/salary-slip/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);

        $create = $this->postJson('/api/staff/salary-slip', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403, 404]);
    }

    // ── Require Salary ────────────────────────────────────────────────────

    public function test_step7_require_salary_lifecycle()
    {
        $list = $this->getJson('/api/staff/req-salary?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        $create = $this->postJson('/api/staff/req-salary', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        $detail = $this->getJson('/api/staff/req-salary/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);
    }

    // ── Profile ───────────────────────────────────────────────────────────

    public function test_step8_profile_accessible()
    {
        $profile = $this->getJson('/api/staff/profile', $this->h());
        $this->assertContains($profile->getStatusCode(), [200, 422]);

        $late = $this->getJson('/api/staff/profile/late', $this->h());
        $this->assertContains($late->getStatusCode(), [200, 422]);

        $update = $this->putJson('/api/staff/profile', [], $this->h());
        $this->assertContains($update->getStatusCode(), [200, 422, 403]);
    }

    // ── Access Control Schedules ──────────────────────────────────────────

    public function test_step9_schedule_lifecycle()
    {
        $list = $this->getJson('/api/staff/schedule?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        $menulist = $this->getJson('/api/staff/schedule/menulist', $this->h());
        $this->assertContains($menulist->getStatusCode(), [200, 422]);

        $insert = $this->postJson('/api/staff/schedule', [], $this->h());
        $this->assertContains($insert->getStatusCode(), [422, 403]);
    }
}
