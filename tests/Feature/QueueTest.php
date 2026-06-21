<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class QueueTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/queue ───────────────────────────────────────────────────────

    public function test_get_queue_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/queue')->assertStatus(401);
    }

    public function test_get_queue_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/queue', $this->auth())->assertStatus(200);
    }

    // ─── POST /api/queue ──────────────────────────────────────────────────────

    public function test_create_queue_tanpa_auth_ditolak()
    {
        $this->postJson('/api/queue', [])->assertStatus(401);
    }

    public function test_create_queue_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/queue', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/queue/convert ──────────────────────────────────────────────

    public function test_convert_queue_dari_booking_tanpa_auth_ditolak()
    {
        $this->postJson('/api/queue/convert', [])->assertStatus(401);
    }

    public function test_convert_queue_dari_booking_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/queue/convert', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── PUT /api/queue/status ────────────────────────────────────────────────

    public function test_update_queue_status_tanpa_auth_ditolak()
    {
        $this->putJson('/api/queue/status', [])->assertStatus(401);
    }

    public function test_update_queue_status_gagal_tanpa_required_fields()
    {
        $response = $this->putJson('/api/queue/status', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── PUT /api/queue/reset ─────────────────────────────────────────────────

    public function test_reset_queue_tanpa_auth_ditolak()
    {
        $this->putJson('/api/queue/reset', [])->assertStatus(401);
    }

    // ─── DELETE /api/queue ────────────────────────────────────────────────────

    public function test_delete_queue_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/queue', [])->assertStatus(401);
    }

    public function test_delete_queue_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/queue', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/queue/booking-candidates ───────────────────────────────────

    public function test_get_booking_candidates_tanpa_auth_ditolak()
    {
        $this->getJson('/api/queue/booking-candidates')->assertStatus(401);
    }

    public function test_get_booking_candidates_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/queue/booking-candidates', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }
}
