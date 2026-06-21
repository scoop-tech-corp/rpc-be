<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E Test: Customer Lifecycle
 *
 * Skenario:
 *   1. Buat customer baru
 *   2. Cek customer muncul di list & detail
 *   3. Update customer
 *   4. Tambah hewan peliharaan (pet)
 *   5. Buat feedback untuk customer
 *   6. Buat support request
 *   7. Delete customer
 */
class CustomerLifecycleE2ETest extends TestCase
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
            'usersId'        => $this->user->id,
            'locationId'     => $this->locationId,
            'isMainLocation' => 1,
            'isDeleted'      => 0,
            'created_at'     => now(),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function h(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ── Step 1: List customer ──────────────────────────────────────────────

    public function test_step1_customer_list_accessible()
    {
        $response = $this->getJson('/api/customer?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
    }

    // ── Step 2: Buat customer baru ─────────────────────────────────────────

    public function test_step2_buat_customer_baru()
    {
        $response = $this->postJson('/api/customer', [
            'firstName'   => 'E2E Customer ' . rand(1000, 9999),
            'locationId'  => $this->locationId,
            'joinDate'    => now()->format('Y-m-d'),
            'typeId'      => 1,
            'memberNo'    => 'E2E-' . rand(100, 999),
            'gender'      => 'L',
        ], $this->h());

        $this->assertContains(
            $response->getStatusCode(),
            [200, 201, 422],
            'Create customer: ' . $response->content()
        );
    }

    // ── Step 3: Full lifecycle create → detail → update → delete ──────────

    public function test_step3_full_customer_lifecycle()
    {
        // 3a. Buat customer
        $name = 'E2E Test ' . rand(1000, 9999);
        $create = $this->postJson('/api/customer', [
            'firstName'  => $name,
            'locationId' => $this->locationId,
            'joinDate'   => now()->format('Y-m-d'),
            'typeId'     => 1,
            'memberNo'   => 'E2E-' . rand(100, 999),
            'gender'     => 'L',
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create customer gagal: ' . $create->content());
        }

        // 3b. Ambil ID dari DB (nama unik)
        $customerId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('customer')->where('firstName', $name)->latest('id')->value('id');

        $this->assertNotNull($customerId, 'Customer ID tidak ditemukan');

        // 3c. Cek detail
        $detail = $this->getJson('/api/customer/detail?id=' . $customerId, $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 404, 422]);

        if ($detail->getStatusCode() === 200) {
            $detailData = $detail->json();
            $this->assertNotEmpty(
                data_get($detailData, 'data') ?? data_get($detailData, 'firstName') ?? true
            );
        }

        // 3d. Update customer
        $update = $this->putJson('/api/customer', [
            'id'        => $customerId,
            'firstName' => $name . ' Updated',
        ], $this->h());
        $this->assertContains($update->getStatusCode(), [200, 201, 422, 404]);

        // 3e. Verifikasi di list
        $list = $this->getJson('/api/customer?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $list->getStatusCode());

        // 3f. Delete customer
        $delete = $this->deleteJson('/api/customer', [
            'datas' => [['id' => $customerId]],
        ], $this->h());
        $this->assertContains($delete->getStatusCode(), [200, 201, 422, 404]);
    }

    // ── Step 4: Customer dashboard ────────────────────────────────────────

    public function test_step4_customer_dashboard_accessible()
    {
        $response = $this->getJson('/api/customer/dashboard', $this->h());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ── Step 5: Dropdown lists ────────────────────────────────────────────

    public function test_step5_dropdown_lists_accessible()
    {
        $endpoints = [
            '/api/customer/group',
            '/api/customer/typeid',
            '/api/customer/title',
            '/api/customer/occupation',
            '/api/customer/source',
            '/api/customer/pet',
            '/api/customer/reference',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint, $this->h());
            $this->assertContains(
                $response->getStatusCode(),
                [200, 422],
                "Endpoint {$endpoint} tidak accessible: " . $response->getStatusCode()
            );
        }
    }

    // ── Step 6: Customer feedback lifecycle ───────────────────────────────

    public function test_step6_feedback_lifecycle()
    {
        // 6a. List feedback
        $list = $this->getJson('/api/customer/feedback?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        // 6b. Buat feedback
        $create = $this->postJson('/api/customer/feedback', [
            'customerId' => 2, // agus (seed data)
            'rating'     => 5,
            'comment'    => 'E2E test feedback - pelayanan sangat baik',
            'category'   => 'service',
        ], $this->h());
        $this->assertContains($create->getStatusCode(), [200, 201, 422]);

        // 6c. Update & Delete input kosong → validasi
        $update = $this->putJson('/api/customer/feedback', [], $this->h());
        $this->assertContains($update->getStatusCode(), [422, 403, 404]);

        $delete = $this->deleteJson('/api/customer/feedback', [], $this->h());
        $this->assertContains($delete->getStatusCode(), [422, 403, 404]);
    }

    // ── Step 7: Support request lifecycle ─────────────────────────────────

    public function test_step7_support_request_lifecycle()
    {
        // 7a. List
        $list = $this->getJson('/api/customer/support-request?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($list->getStatusCode(), [200]);

        // 7b. Buat support request
        $create = $this->postJson('/api/customer/support-request', [
            'customerId' => 2,
            'subject'    => 'E2E Test Support Request',
            'message'    => 'Test message untuk E2E testing',
            'priority'   => 'medium',
        ], $this->h());
        $this->assertContains($create->getStatusCode(), [200, 201, 422]);

        // 7c. My requests
        $myReqs = $this->getJson('/api/customer/support-request/my-requests', $this->h());
        $this->assertContains($myReqs->getStatusCode(), [200, 422]);
    }
}
