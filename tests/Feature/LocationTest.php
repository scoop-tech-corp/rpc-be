<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LocationTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/location ────────────────────────────────────────────────────

    public function test_get_location_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location')->assertStatus(401);
    }

    public function test_get_location_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/location?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    // ─── GET /api/location/list ───────────────────────────────────────────────

    public function test_get_location_list_dropdown_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/list')->assertStatus(401);
    }

    public function test_get_location_list_dropdown_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/list', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/location/list/transaction ───────────────────────────────────

    public function test_get_location_list_transaction_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/list/transaction')->assertStatus(401);
    }

    public function test_get_location_list_transaction_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/list/transaction', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/location/detaillocation ────────────────────────────────────

    public function test_get_location_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/detaillocation?id=1')->assertStatus(401);
    }

    public function test_get_location_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/location/detaillocation?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 404, 422]);
    }

    // ─── POST /api/location — create ─────────────────────────────────────────

    public function test_create_location_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location', [])->assertStatus(401);
    }

    public function test_create_location_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── PUT /api/location — update ──────────────────────────────────────────

    public function test_update_location_tanpa_auth_ditolak()
    {
        $this->putJson('/api/location', [])->assertStatus(401);
    }

    public function test_update_location_gagal_tanpa_required_fields()
    {
        $response = $this->putJson('/api/location', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── DELETE /api/location ─────────────────────────────────────────────────

    public function test_delete_location_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/location', [])->assertStatus(401);
    }

    public function test_delete_location_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/location', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/location/datastaticlocation ─────────────────────────────────

    public function test_get_datastatic_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/datastaticlocation')->assertStatus(401);
    }

    public function test_get_datastatic_location_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/location/datastaticlocation', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── GET /api/location/provinsilocation ───────────────────────────────────

    public function test_get_provinsi_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/provinsilocation')->assertStatus(401);
    }

    public function test_get_provinsi_location_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/provinsilocation', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/location/kabupatenkotalocation ──────────────────────────────

    public function test_get_kabupaten_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/kabupatenkotalocation')->assertStatus(401);
    }

    public function test_get_kabupaten_location_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/location/kabupatenkotalocation', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── POST /api/location/datastatic ────────────────────────────────────────

    public function test_insert_datastatic_location_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/datastatic', [])->assertStatus(401);
    }

    public function test_insert_datastatic_location_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/datastatic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── DELETE /api/location/datastatic ──────────────────────────────────────

    public function test_delete_datastatic_location_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/location/datastatic', [])->assertStatus(401);
    }

    public function test_delete_datastatic_location_gagal_tanpa_required_fields()
    {
        $response = $this->deleteJson('/api/location/datastatic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── GET /api/location/datastatic ─────────────────────────────────────────

    public function test_get_datastatic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/datastatic')->assertStatus(401);
    }

    public function test_get_datastatic_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/location/datastatic', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ─── GET /api/location/product/transfer ───────────────────────────────────

    public function test_get_product_transfer_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/product/transfer')->assertStatus(401);
    }

    public function test_get_product_transfer_location_gagal_tanpa_required_params()
    {
        $this->getJson('/api/location/product/transfer', $this->auth())->assertStatus(422);
    }

    // ─── Facility CRUD ────────────────────────────────────────────────────────

    public function test_get_facility_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/facility')->assertStatus(401);
    }

    public function test_get_facility_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/facility?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_facility_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/facility', [])->assertStatus(401);
    }

    public function test_create_facility_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/facility', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_update_facility_tanpa_auth_ditolak()
    {
        $this->putJson('/api/location/facility', [])->assertStatus(401);
    }

    public function test_delete_facility_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/location/facility', [])->assertStatus(401);
    }

    // ─── Facility detail & images ─────────────────────────────────────────────

    public function test_get_facility_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/facility/facilitydetail?id=1')->assertStatus(401);
    }

    public function test_get_facility_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/facility/facilitylocation')->assertStatus(401);
    }

    public function test_get_facility_location_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/location/facility/facilitylocation', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    public function test_get_facility_with_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/facility/location')->assertStatus(401);
    }

    public function test_get_facility_with_location_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/facility/location', $this->auth())->assertStatus(200);
    }

    // ─── Cage Management CRUD ─────────────────────────────────────────────────

    public function test_get_cage_management_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/cage-management')->assertStatus(401);
    }

    public function test_get_cage_management_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/location/cage-management?rowPerPage=10&goToPage=1', $this->auth())
             ->assertStatus(200);
    }

    public function test_create_cage_management_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/cage-management', [])->assertStatus(401);
    }

    public function test_create_cage_management_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/cage-management', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    public function test_get_cage_management_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/cage-management/detail?id=1')->assertStatus(401);
    }

    public function test_update_cage_management_tanpa_auth_ditolak()
    {
        $this->putJson('/api/location/cage-management', [])->assertStatus(401);
    }

    public function test_delete_cage_management_tanpa_auth_ditolak()
    {
        $this->deleteJson('/api/location/cage-management', [])->assertStatus(401);
    }

    // ─── Cage Inspection ──────────────────────────────────────────────────────

    public function test_get_cage_inspection_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/cage-management/inspection?id=1')->assertStatus(401);
    }

    public function test_create_cage_inspection_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/cage-management/inspection', [])->assertStatus(401);
    }

    public function test_create_cage_inspection_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/cage-management/inspection', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Cage Maintenance ─────────────────────────────────────────────────────

    public function test_get_cage_maintenance_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/cage-management/maintenance?id=1')->assertStatus(401);
    }

    public function test_create_cage_maintenance_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/cage-management/maintenance', [])->assertStatus(401);
    }

    public function test_create_cage_maintenance_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/cage-management/maintenance', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ─── Cage Cleaning Log ────────────────────────────────────────────────────

    public function test_get_cage_cleaning_log_tanpa_auth_ditolak()
    {
        $this->getJson('/api/location/cage-management/cleaning-log?id=1')->assertStatus(401);
    }

    public function test_create_cage_cleaning_log_tanpa_auth_ditolak()
    {
        $this->postJson('/api/location/cage-management/cleaning-log', [])->assertStatus(401);
    }

    public function test_create_cage_cleaning_log_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/location/cage-management/cleaning-log', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }
}
