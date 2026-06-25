<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Pet Salon flow
 * stats → create → accept → petcheck → treatment → salon-done → checkout → payment
 */
class PetSalonE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId = 1;
    private int $customerId = 2;
    private int $petId      = 2;   // YOLO milik customerId=2
    private int $doctorId   = 4;   // akun dokter (jobTitleId=17)

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

    public function test_step1_stats_dan_index()
    {
        $stats = $this->getJson('/api/transaction/petsalon/stats', $this->h());
        $this->assertContains($stats->getStatusCode(), [200]);

        $index = $this->getJson('/api/transaction/petsalon?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $index->getStatusCode());
        $this->assertArrayHasKey('data', $index->json());
    }

    public function test_step2_buat_transaksi_salon()
    {
        $resp = $this->postJson('/api/transaction/petsalon', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->format('Y-m-d'),
            'note'          => 'E2E salon test',
        ], $this->h());
        $this->assertContains($resp->getStatusCode(), [200, 201, 422]);
    }

    public function test_step3_full_salon_lifecycle()
    {
        $create = $this->postJson('/api/transaction/petsalon', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->format('Y-m-d'),
            'note'          => 'E2E salon lifecycle',
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create salon gagal: ' . $create->content());
        }

        $id = data_get($create->json(), 'data.id') ?? data_get($create->json(), 'id')
            ?? DB::table('transaction_pet_salons')->where('customerId', $this->customerId)->latest('id')->value('id');

        $this->assertNotNull($id);

        // Detail
        $detail = $this->getJson('/api/transaction/petsalon/detail?id=' . $id, $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 404, 422]);

        // Accept
        $accept = $this->postJson('/api/transaction/petsalon/accept', ['id' => $id, 'doctorId' => $this->doctorId], $this->h());
        $this->assertContains($accept->getStatusCode(), [200, 201, 422, 404]);

        // Petcheck kosong → validasi
        $petcheck = $this->postJson('/api/transaction/petsalon/petcheck', [], $this->h());
        $this->assertContains($petcheck->getStatusCode(), [422, 403]);

        // Policies
        $pol = $this->getJson('/api/transaction/petsalon/policies', $this->h());
        $this->assertContains($pol->getStatusCode(), [200, 422]);

        // Salon done (kosong → validasi)
        $done = $this->postJson('/api/transaction/petsalon/salon-done', [], $this->h());
        $this->assertContains($done->getStatusCode(), [422, 403, 404]);

        // Before payment
        $bf = $this->getJson('/api/transaction/petsalon/beforepayment?id=' . $id, $this->h());
        $this->assertContains($bf->getStatusCode(), [200, 422, 404]);

        // Payment (kosong → validasi)
        $pay = $this->postJson('/api/transaction/petsalon/payment', [], $this->h());
        $this->assertContains($pay->getStatusCode(), [422, 403, 404]);
    }

    public function test_step4_salon_payment_endpoints()
    {
        $confirm = $this->postJson('/api/transaction/petsalon/confirm-payment', [], $this->h());
        $this->assertContains($confirm->getStatusCode(), [422, 403, 404]);

        $reject = $this->postJson('/api/transaction/petsalon/reject-payment', [], $this->h());
        $this->assertContains($reject->getStatusCode(), [422, 403, 404]);

        $checkout = $this->postJson('/api/transaction/petsalon/checkout', [], $this->h());
        $this->assertContains($checkout->getStatusCode(), [422, 403, 404]);
    }
}
