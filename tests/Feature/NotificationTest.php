<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/notifications ───────────────────────────────────────────────

    public function test_get_notifications_tanpa_auth_ditolak()
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_get_notifications_dengan_auth_berhasil()
    {
        $this->getJson('/api/notifications', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/notifications/read/{id} ───────────────────────────────────

    public function test_mark_notification_read_tanpa_auth_ditolak()
    {
        $this->postJson('/api/notifications/read/1')->assertStatus(401);
    }

    public function test_mark_notification_read_id_tidak_ada()
    {
        $response = $this->postJson('/api/notifications/read/99999', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422]);
    }

    // ─── POST /api/notifications/read-all ────────────────────────────────────

    public function test_mark_all_notifications_read_tanpa_auth_ditolak()
    {
        $this->postJson('/api/notifications/read-all')->assertStatus(401);
    }

    public function test_mark_all_notifications_read_dengan_auth_berhasil()
    {
        $this->postJson('/api/notifications/read-all', [], $this->auth())->assertStatus(200);
    }
}
