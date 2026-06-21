<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ProductTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function test_product_dashboard_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/dashboard')->assertStatus(401);
    }

    public function test_product_dashboard_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/dashboard', $this->auth())->assertStatus(200);
    }

    // ─── Supplier ────────────────────────────────────────────────────────────

    public function test_get_supplier_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/supplier')->assertStatus(401);
    }

    public function test_get_supplier_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/supplier?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_supplier_tanpa_auth_ditolak()
    {
        $this->postJson('/api/product/supplier', [])->assertStatus(401);
    }

    public function test_create_supplier_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/supplier', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_supplier_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/product/supplier', ['id' => 1])->assertStatus(401);
    }

    public function test_get_supplier_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/supplier/detail?id=1')->assertStatus(401);
    }

    // ─── Product Brand ───────────────────────────────────────────────────────

    public function test_get_brand_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/brand')->assertStatus(401);
    }

    public function test_get_brand_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/brand', $this->auth())->assertStatus(200);
    }

    public function test_create_brand_tanpa_auth_ditolak()
    {
        $this->postJson('/api/product/brand', [])->assertStatus(401);
    }

    public function test_create_brand_gagal_tanpa_nama()
    {
        $response = $this->postJson('/api/product/brand', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Product Sell ─────────────────────────────────────────────────────────

    public function test_get_product_sell_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/sell')->assertStatus(401);
    }

    public function test_get_product_sell_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/sell?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_product_sell_tanpa_auth_ditolak()
    {
        $this->postJson('/api/product/sell', [])->assertStatus(401);
    }

    public function test_create_product_sell_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/sell', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_product_sell_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/product/sell', ['id' => 1])->assertStatus(401);
    }

    // ─── Product Clinic ───────────────────────────────────────────────────────

    public function test_get_product_clinic_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/clinic')->assertStatus(401);
    }

    public function test_get_product_clinic_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/clinic?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_product_clinic_tanpa_auth_ditolak()
    {
        $this->postJson('/api/product/clinic', [])->assertStatus(401);
    }

    public function test_create_product_clinic_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/clinic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_delete_product_clinic_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/product/clinic', ['id' => 1])->assertStatus(401);
    }

    // ─── Inventory ────────────────────────────────────────────────────────────

    public function test_get_inventory_tanpa_auth_ditolak()
    {
        $this->getJson('/api/product/inventory')->assertStatus(401);
    }

    public function test_get_inventory_dengan_auth_berhasil()
    {
        $this->getJson('/api/product/inventory?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }
}
