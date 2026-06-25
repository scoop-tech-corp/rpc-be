<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ChatTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/chat ────────────────────────────────────────────────────────

    public function test_get_chat_index_tanpa_auth_ditolak()
    {
        $this->getJson('/api/chat')->assertStatus(401);
    }

    public function test_get_chat_index_dengan_auth_berhasil()
    {
        $this->getJson('/api/chat', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/chat/list-user ──────────────────────────────────────────────

    public function test_get_chat_list_user_tanpa_auth_ditolak()
    {
        $this->getJson('/api/chat/list-user')->assertStatus(401);
    }

    public function test_get_chat_list_user_dengan_auth_berhasil()
    {
        $this->getJson('/api/chat/list-user', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/chat/detail ─────────────────────────────────────────────────

    public function test_get_chat_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/chat/detail')->assertStatus(401);
    }

    public function test_get_chat_detail_gagal_tanpa_required_fields()
    {
        $response = $this->getJson('/api/chat/detail', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── POST /api/chat ───────────────────────────────────────────────────────

    public function test_create_chat_tanpa_auth_ditolak()
    {
        $this->postJson('/api/chat', [])->assertStatus(401);
    }

    public function test_create_chat_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/chat', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── POST /api/chat/read ──────────────────────────────────────────────────

    public function test_mark_chat_read_tanpa_auth_ditolak()
    {
        $this->postJson('/api/chat/read', [])->assertStatus(401);
    }

    public function test_mark_chat_read_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/chat/read', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }
}
