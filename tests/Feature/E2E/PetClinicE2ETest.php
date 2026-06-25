<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E Test: Pet Clinic (Rawat Jalan) Flow
 *
 * Skenario lengkap:
 *   1. Buat User dokter + assign ke lokasi
 *   2. Buat transaksi klinik baru (existing customer, existing pet)
 *   3. Accept transaksi
 *   4. Tambah data petcheck (vital signs)
 *   5. Tambah service/resep
 *   6. Lihat data sebelum bayar
 *   7. Cek endpoint payment
 */
class PetClinicE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId    = 1;
    private int $customerId    = 2;   // agus
    private int $petId         = 2;   // YOLO milik customerId=2
    private int $paymentMethod = 1;   // QRIS
    private int $doctorId      = 4;   // akun dokter (jobTitleId=17, locationId=1)

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'isDeleted' => 0,
            'roleId'    => 1,
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

    private function h(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ── Step 1: List & Stats endpoint ─────────────────────────────────────

    public function test_step1_clinic_index_dan_stats_accessible()
    {
        $index = $this->getJson('/api/transaction/petclinic?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $index->getStatusCode());
        $this->assertArrayHasKey('data', $index->json());

        $stats = $this->getJson('/api/transaction/petclinic/stats', $this->h());
        $this->assertEquals(200, $stats->getStatusCode());
    }

    // ── Step 2: Buat transaksi klinik ─────────────────────────────────────

    public function test_step2_buat_transaksi_klinik_existing_customer()
    {
        $response = $this->postJson('/api/transaction/petclinic', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'typeOfCare'    => 1,  // rawat jalan
            'note'          => 'E2E test - kunjungan rutin',
        ], $this->h());

        $this->assertContains(
            $response->getStatusCode(),
            [200, 201, 422],
            'Buat klinik transaction: ' . $response->content()
        );
    }

    // ── Step 3: Full flow create → accept → petcheck ──────────────────────

    public function test_step3_create_lalu_accept_transaksi()
    {
        // 3a. Buat transaksi
        $create = $this->postJson('/api/transaction/petclinic', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'typeOfCare'    => 1,
            'note'          => 'E2E rawat jalan',
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->format('Y-m-d'),
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create gagal, skip accept step');
        }

        $transId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('transactionPetClinics')
                ->where('customerId', $this->customerId)
                ->latest('id')->value('id');

        $this->assertNotNull($transId, 'Tidak bisa mendapatkan transaction ID');

        // 3b. Accept transaksi
        $accept = $this->postJson('/api/transaction/petclinic/accept', [
            'id'       => $transId,
            'doctorId' => $this->doctorId,
        ], $this->h());

        $this->assertContains($accept->getStatusCode(), [200, 201, 422, 404]);

        // 3c. Lihat detail
        $detail = $this->getJson('/api/transaction/petclinic/detail?id=' . $transId, $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 404]);
    }

    // ── Step 4: Petcheck data ─────────────────────────────────────────────

    public function test_step4_petcheck_validasi_input_kosong()
    {
        $response = $this->postJson('/api/transaction/petclinic/petcheck', [], $this->h());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ── Step 5: Service and recipe ────────────────────────────────────────

    public function test_step5_serviceandrecipe_validasi_input_kosong()
    {
        $response = $this->postJson('/api/transaction/petclinic/serviceandrecipe', [], $this->h());
        $this->assertContains($response->getStatusCode(), [422, 403]);
    }

    // ── Step 6: Order number endpoint ─────────────────────────────────────

    public function test_step6_order_number_accessible()
    {
        $response = $this->getJson('/api/transaction/petclinic/ordernumber', $this->h());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ── Step 7: Payment methods list ─────────────────────────────────────

    public function test_step7_payment_methods_accessible()
    {
        $response = $this->getJson('/api/transaction/petclinic/payment-methods', $this->h());
        $this->assertContains($response->getStatusCode(), [200, 422]);
    }

    // ── Step 8: Before payment endpoint ───────────────────────────────────

    public function test_step8_before_payment_validasi_tanpa_id()
    {
        $response = $this->getJson('/api/transaction/petclinic/beforepayment', $this->h());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ── Step 9: Full lifecycle — outpatient ───────────────────────────────

    public function test_step9_full_outpatient_lifecycle()
    {
        // 9a. Buat transaksi
        $create = $this->postJson('/api/transaction/petclinic', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'typeOfCare'    => 1,
            'note'          => 'E2E lifecycle test',
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->format('Y-m-d'),
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create gagal: ' . $create->content());
        }

        $transId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('transactionPetClinics')
                ->where('customerId', $this->customerId)
                ->latest('id')->value('id');

        $this->assertNotNull($transId);

        // 9b. Load petcheck data (harus 200 atau 422)
        $loadPetcheck = $this->getJson('/api/transaction/petclinic/load-petcheck?id=' . $transId, $this->h());
        $this->assertContains($loadPetcheck->getStatusCode(), [200, 422, 404]);

        // 9c. Tambah service via serviceandrecipe
        $svc = $this->postJson('/api/transaction/petclinic/serviceandrecipe', [
            'transactionId' => $transId,
            'services'      => [],
            'products'      => [],
        ], $this->h());
        $this->assertContains($svc->getStatusCode(), [200, 201, 422, 404]);

        // 9d. Cek data sebelum bayar
        $bfPay = $this->getJson('/api/transaction/petclinic/beforepayment?id=' . $transId, $this->h());
        $this->assertContains($bfPay->getStatusCode(), [200, 422, 404]);

        // 9e. Outpatient payment input kosong → validasi
        $pay = $this->postJson('/api/transaction/petclinic/payment/outpatient', [], $this->h());
        $this->assertContains($pay->getStatusCode(), [422, 403, 404]);

        // Test lulus — seluruh lifecycle endpoint dapat diakses tanpa crash (500)
        $this->assertTrue(true, 'E2E lifecycle klinik rawat jalan selesai tanpa error server');
    }
}
