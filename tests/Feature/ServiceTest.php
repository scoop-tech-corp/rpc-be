<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_service_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/dashboard')->assertStatus(401);
    }

    public function test_service_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/dashboard', $this->auth())->assertStatus(200);
    }

    // ─── Category ─────────────────────────────────────────────────────────────

    public function test_get_service_category_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/category')->assertStatus(401);
    }

    public function test_get_service_category_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/category?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_service_category_tanpa_auth_ditolak()
    {
        $this->postJson('/api/service/category', [])->assertStatus(401);
    }

    public function test_create_service_category_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/service/category', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_service_category_tanpa_auth_ditolak()
    {
        $this->putJson('/api/service/category', ['id' => 1])->assertStatus(401);
    }

    public function test_delete_service_category_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/service/category', ['id' => 1])->assertStatus(401);
    }

    // ─── Service List ─────────────────────────────────────────────────────────

    public function test_get_service_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/list')->assertStatus(401);
    }

    public function test_get_service_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/list?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_service_tanpa_auth_ditolak()
    {
        $this->postJson('/api/service/list', [])->assertStatus(401);
    }

    public function test_create_service_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/service/list', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_service_tanpa_auth_ditolak()
    {
        $this->putJson('/api/service/list', [])->assertStatus(401);
    }

    public function test_delete_service_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/service/list', ['id' => 1])->assertStatus(401);
    }

    // ─── Treatment ────────────────────────────────────────────────────────────

    public function test_get_treatment_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/treatment')->assertStatus(401);
    }

    public function test_get_treatment_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/treatment?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_treatment_tanpa_auth_ditolak()
    {
        $this->postJson('/api/service/treatment', [])->assertStatus(401);
    }

    public function test_create_treatment_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/service/treatment', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_treatment_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/service/treatment', ['id' => 1])->assertStatus(401);
    }

    // ─── Diagnose ────────────────────────────────────────────────────────────

    public function test_get_diagnose_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/diagnose')->assertStatus(401);
    }

    public function test_get_diagnose_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/diagnose?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    // ─── Frequency ───────────────────────────────────────────────────────────

    public function test_get_frequency_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/frequency')->assertStatus(401);
    }

    public function test_get_frequency_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/frequency', $this->auth())->assertStatus(200);
    }

    // ─── Task ─────────────────────────────────────────────────────────────────

    public function test_get_task_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/task')->assertStatus(401);
    }

    public function test_get_task_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/task', $this->auth())->assertStatus(200);
    }

    // ─── Contract ─────────────────────────────────────────────────────────────

    public function test_get_contract_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/service/contract')->assertStatus(401);
    }

    public function test_get_contract_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/service/contract?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_contract_tanpa_auth_ditolak()
    {
        $this->postJson('/api/service/contract', [])->assertStatus(401);
    }

    public function test_create_contract_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/service/contract', [], $this->auth());
        $response->assertStatus(422);
    }

    public function test_delete_contract_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/service/contract', ['id' => 1])->assertStatus(401);
    }
}
