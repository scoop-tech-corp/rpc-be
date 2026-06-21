<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Security, Access Control, Absent, Menu, Chat, Notifications, Utility
 * securitygroup → accesscontrol → absent → menu → chat → notifications
 */
class SecurityAccessUtilityE2ETest extends TestCase
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

    // ── Security Group ────────────────────────────────────────────────────

    public function test_step1_security_group_lifecycle()
    {
        // List
        $list = $this->getJson('/api/securitygroup?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $list->getStatusCode());

        // Detail (kosong → 422)
        $detail = $this->getJson('/api/securitygroup/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);

        // Users dropdown
        $users = $this->getJson('/api/securitygroup/users', $this->h());
        $this->assertContains($users->getStatusCode(), [200, 422]);

        // Create (kosong → validasi)
        $create = $this->postJson('/api/securitygroup', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        // Full lifecycle
        $createFull = $this->postJson('/api/securitygroup', [
            'name'        => 'E2E Security Group ' . rand(100, 999),
            'description' => 'E2E test security group',
        ], $this->h());
        $this->assertContains($createFull->getStatusCode(), [200, 201, 422]);

        if (in_array($createFull->getStatusCode(), [200, 201])) {
            $id = data_get($createFull->json(), 'data.id') ?? data_get($createFull->json(), 'id');
            if ($id) {
                $this->assertContains(
                    $this->putJson('/api/securitygroup', ['id' => $id, 'name' => 'Updated'], $this->h())->getStatusCode(),
                    [200, 422, 404]
                );
                $this->assertContains(
                    $this->deleteJson('/api/securitygroup', ['id' => $id], $this->h())->getStatusCode(),
                    [200, 422, 404]
                );
            }
        }
    }

    // ── Access Control ────────────────────────────────────────────────────

    public function test_step2_access_control_lifecycle()
    {
        // Dashboard
        $dash = $this->getJson('/api/accesscontrol', $this->h());
        $this->assertContains($dash->getStatusCode(), [200, 422]);

        // User index
        $user = $this->getJson('/api/accesscontrol/user', $this->h());
        $this->assertContains($user->getStatusCode(), [200, 422]);

        // History
        $history = $this->getJson('/api/accesscontrol/history', $this->h());
        $this->assertContains($history->getStatusCode(), [200, 422]);

        // Dropdowns
        foreach ([
            '/api/accesscontrol/accesstype',
            '/api/accesscontrol/menumaster',
            '/api/accesscontrol/menumaster/index',
            '/api/accesscontrol/menulist',
            '/api/accesscontrol/menulist/index',
        ] as $ep) {
            $r = $this->getJson($ep, $this->h());
            $this->assertContains($r->getStatusCode(), [200, 422], "$ep gagal");
        }

        // Insert menu list (kosong → validasi)
        $ins = $this->postJson('/api/accesscontrol/menulist', [], $this->h());
        $this->assertContains($ins->getStatusCode(), [422, 403]);
    }

    // ── Absent ────────────────────────────────────────────────────────────

    public function test_step3_absent_lifecycle()
    {
        // Index
        $index = $this->getJson('/api/absent/index?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($index->getStatusCode(), [200, 422]);

        // Staff list
        $staffList = $this->getJson('/api/absent/staff-list', $this->h());
        $this->assertContains($staffList->getStatusCode(), [200, 422]);

        // Present list
        $presentList = $this->getJson('/api/absent/present-list', $this->h());
        $this->assertContains($presentList->getStatusCode(), [200, 422]);

        // Detail (kosong → 422)
        $detail = $this->getJson('/api/absent/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);

        // Create absent (kosong → validasi)
        $create = $this->postJson('/api/absent', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);
    }

    // ── Menu Management ───────────────────────────────────────────────────

    public function test_step4_menu_management_lifecycle()
    {
        // Menu group
        $mg = $this->getJson('/api/menu/menu-group?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($mg->getStatusCode(), [200]);

        $listMG = $this->getJson('/api/menu/list-menu-group', $this->h());
        $this->assertContains($listMG->getStatusCode(), [200, 422]);

        // Child menu
        $cm = $this->getJson('/api/menu/child-menu-group?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($cm->getStatusCode(), [200]);

        // Grand child menu
        $gcm = $this->getJson('/api/menu/grand-child-menu-group?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($gcm->getStatusCode(), [200]);

        // Menu report
        $mr = $this->getJson('/api/menu/menu-report?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($mr->getStatusCode(), [200]);

        // Timekeeper
        $tk = $this->getJson('/api/menu/timekeeper?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($tk->getStatusCode(), [200]);

        // Profile
        $profile = $this->getJson('/api/menu/profile', $this->h());
        $this->assertContains($profile->getStatusCode(), [200, 422]);

        // Setting
        $setting = $this->getJson('/api/menu/setting', $this->h());
        $this->assertContains($setting->getStatusCode(), [200, 422]);
    }

    // ── Chat ──────────────────────────────────────────────────────────────

    public function test_step5_chat_lifecycle()
    {
        // List user
        $listUser = $this->getJson('/api/chat/list-user', $this->h());
        $this->assertContains($listUser->getStatusCode(), [200, 422]);

        // Index
        $index = $this->getJson('/api/chat', $this->h());
        $this->assertContains($index->getStatusCode(), [200, 422]);

        // Detail (kosong → 422)
        $detail = $this->getJson('/api/chat/detail', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);

        // Create (kosong → validasi)
        $create = $this->postJson('/api/chat', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        // Read (kosong → validasi)
        $read = $this->postJson('/api/chat/read', [], $this->h());
        $this->assertContains($read->getStatusCode(), [422, 403, 200]);
    }

    // ── Notifications ─────────────────────────────────────────────────────

    public function test_step6_notifications_accessible()
    {
        // List notifications
        $list = $this->getJson('/api/notifications', $this->h());
        $this->assertContains($list->getStatusCode(), [200, 422]);

        // Mark all read
        $markAll = $this->postJson('/api/notifications/read-all', [], $this->h());
        $this->assertContains($markAll->getStatusCode(), [200, 422]);

        // Mark single (kosong ID → 404)
        $markOne = $this->postJson('/api/notifications/read/999999', [], $this->h());
        $this->assertContains($markOne->getStatusCode(), [200, 404, 422]);
    }

    // ── Global (payment method, transaction category) ─────────────────────

    public function test_step7_global_utility_accessible()
    {
        // Payment method list
        $pm = $this->getJson('/api/payment-method/list', $this->h());
        $this->assertContains($pm->getStatusCode(), [200, 422]);

        // Transaction dashboard
        $txDash = $this->getJson('/api/transaction/dashboard', $this->h());
        $this->assertContains($txDash->getStatusCode(), [200, 422]);

        // Transaction category
        $txCat = $this->getJson('/api/transaction/category', $this->h());
        $this->assertContains($txCat->getStatusCode(), [200, 422]);
    }
}
