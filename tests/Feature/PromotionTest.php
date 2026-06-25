<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PromotionTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_promotion_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/promotion/dashboard')->assertStatus(401);
    }

    public function test_promotion_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/promotion/dashboard', $this->auth())->assertStatus(200);
    }

    // ─── Discount ────────────────────────────────────────────────────────────

    public function test_get_discount_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/promotion/discount')->assertStatus(401);
    }

    public function test_get_discount_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/promotion/discount?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_get_discount_active_today_tanpa_auth_ditolak()
    {
        $this->getJson('/api/promotion/discount/active-today')->assertStatus(401);
    }

    public function test_get_discount_active_today_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/promotion/discount/active-today', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 403]);
    }

    public function test_get_discount_list_type_dengan_auth_berhasil()
    {
        $this->getJson('/api/promotion/discount/list-type', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_discount_tanpa_auth_ditolak()
    {
        $this->postJson('/api/promotion/discount', [])->assertStatus(401);
    }

    public function test_create_discount_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/promotion/discount', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_discount_tanpa_auth_ditolak()
    {
        $this->putJson('/api/promotion/discount', [])->assertStatus(401);
    }

    public function test_delete_discount_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/promotion/discount', ['id' => 1])->assertStatus(401);
    }

    public function test_check_promo_tanpa_auth_ditolak()
    {
        $this->postJson('/api/promotion/discount/checkpromo', [])->assertStatus(401);
    }

    public function test_check_promo_endpoint_merespons()
    {
        $response = $this->postJson('/api/promotion/discount/checkpromo', [], $this->auth());
        // Endpoint harus merespons, bukan 401 atau 404
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    // ─── Partner ─────────────────────────────────────────────────────────────

    public function test_get_partner_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/promotion/partner')->assertStatus(401);
    }

    public function test_get_partner_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/promotion/partner?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_partner_tanpa_auth_ditolak()
    {
        $this->postJson('/api/promotion/partner', [])->assertStatus(401);
    }

    public function test_create_partner_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/promotion/partner', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_partner_tanpa_auth_ditolak()
    {
        $this->putJson('/api/promotion/partner', [])->assertStatus(401);
    }

    public function test_delete_partner_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/promotion/partner', ['id' => 1])->assertStatus(401);
    }

    // ─── Data Static ─────────────────────────────────────────────────────────

    public function test_get_promotion_datastatic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/promotion/datastatic')->assertStatus(401);
    }

    public function test_get_promotion_datastatic_dengan_auth_berhasil()
    {
        $this->getJson('/api/promotion/datastatic', $this->auth())->assertStatus(200);
    }
}
