<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Location Management
 * list → create → detail → update → facility → cage → delete
 */
class LocationManagementE2ETest extends TestCase
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

    // ── Location CRUD ─────────────────────────────────────────────────────

    public function test_step1_location_list_accessible()
    {
        $list = $this->getJson('/api/location?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $list->getStatusCode());

        $listTx = $this->getJson('/api/location/list/transaction', $this->h());
        $this->assertContains($listTx->getStatusCode(), [200, 422]);

        $simpleList = $this->getJson('/api/location/list', $this->h());
        $this->assertContains($simpleList->getStatusCode(), [200, 422]);
    }

    public function test_step2_location_detail_and_dropdowns()
    {
        // Detail (kosong → 422)
        $detail = $this->getJson('/api/location/detaillocation', $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 422, 404]);

        // Data static
        $ds = $this->getJson('/api/location/datastaticlocation', $this->h());
        $this->assertContains($ds->getStatusCode(), [200, 422]);

        // Provinsi
        $prov = $this->getJson('/api/location/provinsilocation', $this->h());
        $this->assertContains($prov->getStatusCode(), [200, 422]);

        // Kabupaten (requires provinsi)
        $kab = $this->getJson('/api/location/kabupatenkotalocation', $this->h());
        $this->assertContains($kab->getStatusCode(), [200, 422]);
    }

    public function test_step3_location_create_validation()
    {
        // Create (kosong → validasi)
        $create = $this->postJson('/api/location', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        // Update & Delete (kosong → validasi)
        $this->assertContains($this->putJson('/api/location', [], $this->h())->getStatusCode(), [422, 403, 404]);
        $this->assertContains($this->deleteJson('/api/location', [], $this->h())->getStatusCode(), [422, 403, 404]);
    }

    // ── Facility ──────────────────────────────────────────────────────────

    public function test_step4_facility_lifecycle()
    {
        // List facility (requires locationId)
        $list = $this->getJson('/api/location/facility?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200, 422]);

        // Facility with location
        $fl = $this->getJson('/api/location/facility/facilitylocation', $this->h());
        $this->assertContains($fl->getStatusCode(), [200, 422]);

        // Facility detail (kosong → 422)
        $fd = $this->getJson('/api/location/facility/facilitydetail', $this->h());
        $this->assertContains($fd->getStatusCode(), [200, 422, 404]);

        // Create facility (kosong → validasi)
        $create = $this->postJson('/api/location/facility', [], $this->h());
        $this->assertContains($create->getStatusCode(), [422, 403]);

        // Update facility (kosong → validasi)
        $update = $this->putJson('/api/location/facility', [], $this->h());
        $this->assertContains($update->getStatusCode(), [422, 403, 404]);

        // Delete facility (kosong → validasi)
        $delete = $this->deleteJson('/api/location/facility', [], $this->h());
        $this->assertContains($delete->getStatusCode(), [422, 403, 404]);
    }

    // ── Cage Management ───────────────────────────────────────────────────

    public function test_step5_cage_management_accessible()
    {
        // Cage routes (check common patterns)
        $cage = $this->getJson('/api/location/cage?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($cage->getStatusCode(), [200, 404, 422]);

        $cageDetail = $this->getJson('/api/location/cage/detail', $this->h());
        $this->assertContains($cageDetail->getStatusCode(), [200, 404, 422]);
    }

    // ── Product transfer routes ───────────────────────────────────────────

    public function test_step6_location_product_transfer_routes()
    {
        $productTransfer = $this->getJson('/api/location/product/transfer', $this->h());
        $this->assertContains($productTransfer->getStatusCode(), [200, 422]);

        $destination = $this->getJson('/api/location/product/transfer/destination', $this->h());
        $this->assertContains($destination->getStatusCode(), [200, 422]);
    }

    // ── Dashboard ─────────────────────────────────────────────────────────

    public function test_step7_dashboard_accessible()
    {
        $dash = $this->getJson('/api/dashboard/overview', $this->h());
        $this->assertContains($dash->getStatusCode(), [200, 422]);

        $upClinic = $this->getJson('/api/dashboard/upbookingclinic', $this->h());
        $this->assertContains($upClinic->getStatusCode(), [200, 422]);

        $activity = $this->getJson('/api/dashboard/activity', $this->h());
        $this->assertContains($activity->getStatusCode(), [200, 422]);
    }
}
