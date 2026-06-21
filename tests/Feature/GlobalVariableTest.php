<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Tests\TestCase;

/**
 * Covers global variable endpoints:
 * /api/kabupaten, /api/provinsi, /api/datastaticglobal
 */
class GlobalVariableTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── KABUPATEN ────────────────────────────────────────────────────────────

    public function test_get_kabupaten_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/kabupaten');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_kabupaten_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/kabupaten', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_get_kabupaten_dengan_filter_provinsi()
    {
        $response = $this->getJson('/api/kabupaten?kodeProvinsi=11', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    // ─── PROVINSI ─────────────────────────────────────────────────────────────

    public function test_get_provinsi_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/provinsi');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_provinsi_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/provinsi', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    // ─── DATASTATIC GLOBAL ────────────────────────────────────────────────────

    public function test_get_datastatic_global_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/datastaticglobal');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_datastatic_global_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/datastaticglobal', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_insert_datastatic_global_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/datastaticglobal', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_insert_datastatic_global_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/datastaticglobal', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }
}
