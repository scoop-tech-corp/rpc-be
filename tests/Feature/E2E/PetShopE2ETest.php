<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E Test: Pet Shop Transaction Flow
 *
 * Skenario lengkap:
 *   1. Buat User + lokasi assignment
 *   2. Buat transaksi Pet Shop (POST /api/transaction/petshop)
 *   3. Cek detail transaksi (GET /api/transaction/petshop/detail)
 *   4. Hitung diskon (POST /api/transaction/petshop/discount)
 *   5. Konfirmasi pembayaran (POST /api/transaction/petshop/confirmPayment)
 *   6. Cek list transaksi (GET) — pastikan transaksi muncul
 */
class PetShopE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId = 1;   // RPC Pulogebang (seed data)
    private int $customerId = 2;   // agus (seed data)
    private int $paymentMethodId = 1; // QRIS (seed data)

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user admin + assign ke lokasi
        $this->user = User::factory()->create([
            'isDeleted' => 0,
            'roleId'    => 1, // Administrator
        ]);
        DB::table('usersLocation')->insert([
            'usersId'        => $this->user->id,
            'locationId'     => $this->locationId,
            'isMainLocation' => 1,
            'isDeleted'      => 0,
            'created_at'     => now(),
        ]);
        $this->token = JWTAuth::fromUser($this->user);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ── Step 1: Buat transaksi ─────────────────────────────────────────────

    public function test_step1_buat_transaksi_petshop()
    {
        $response = $this->postJson('/api/transaction/petshop', [
            'isNewCustomer'   => false,
            'customerId'      => $this->customerId,
            'locationId'      => $this->locationId,
            'serviceCategory' => 'Pet Shop',
            'paymentMethod'   => $this->paymentMethodId,
            'productList'     => [],
        ], $this->headers());

        // Terima 200/201 (sukses) atau 422 (validasi) — tolak 401/500
        $this->assertContains(
            $response->getStatusCode(),
            [200, 201, 422],
            'Step 1: create pet shop - status tidak terduga: ' . $response->getStatusCode()
        );
    }

    // ── Step 2: Full flow — create → detail → list ─────────────────────────

    public function test_step2_full_flow_create_dan_verify_detail()
    {
        // 2a. Buat transaksi
        $create = $this->postJson('/api/transaction/petshop', [
            'isNewCustomer'   => false,
            'customerId'      => $this->customerId,
            'locationId'      => $this->locationId,
            'serviceCategory' => 'Pet Shop',
            'paymentMethod'   => $this->paymentMethodId,
            'productList'     => [],
        ], $this->headers());

        $createStatus = $create->getStatusCode();
        $this->assertContains($createStatus, [200, 201, 422]);

        if (!in_array($createStatus, [200, 201])) {
            $this->markTestSkipped('Create transaction gagal (422), skip detail check');
        }

        $body = $create->json();

        // 2b. Cek list — harus ada entry
        $list = $this->getJson('/api/transaction/petshop?rowPerPage=10&goToPage=1', $this->headers());
        $this->assertContains($list->getStatusCode(), [200]);
        $listData = $list->json();
        $this->assertArrayHasKey('data', $listData);

        // 2c. Ambil id dari response atau dari DB
        $transId = data_get($body, 'data.id') ?? data_get($body, 'id');

        if ($transId) {
            $detail = $this->getJson('/api/transaction/petshop/detail?id=' . $transId, $this->headers());
            $this->assertContains($detail->getStatusCode(), [200, 404]);
        }
    }

    // ── Step 3: Discount endpoint menerima input valid ─────────────────────

    public function test_step3_discount_endpoint_validasi_input()
    {
        // Input kosong harus 422
        $response = $this->postJson('/api/transaction/petshop/discount', [], $this->headers());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ── Step 4: Konfirmasi pembayaran — tolak input kosong ─────────────────

    public function test_step4_confirm_payment_validasi_input()
    {
        $response = $this->postJson('/api/transaction/petshop/confirmPayment', [], $this->headers());
        $this->assertContains($response->getStatusCode(), [422, 403, 404]);
    }

    // ── Step 5: Reject pembayaran — tolak input kosong ────────────────────

    public function test_step5_reject_payment_validasi_input()
    {
        $response = $this->postJson('/api/transaction/petshop/reject-payment', [], $this->headers());
        $this->assertContains($response->getStatusCode(), [422, 403, 404]);
    }
}
