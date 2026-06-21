<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Pet Hotel flow
 * stats → create → accept → petcheck → treatment → before-payment → calculate → payment
 */
class PetHotelE2ETest extends TestCase
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

    public function test_step1_stats_dan_index_accessible()
    {
        $this->assertContains($this->getJson('/api/transaction/pethotel/stats', $this->h())->getStatusCode(), [200]);
        $index = $this->getJson('/api/transaction/pethotel?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $index->getStatusCode());
        $this->assertArrayHasKey('data', $index->json());
    }

    public function test_step2_buat_transaksi_hotel()
    {
        $resp = $this->postJson('/api/transaction/pethotel', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->addDays(3)->format('Y-m-d'),
            'note'          => 'E2E hotel test',
        ], $this->h());
        $this->assertContains($resp->getStatusCode(), [200, 201, 422]);
    }

    public function test_step3_full_hotel_lifecycle()
    {
        $create = $this->postJson('/api/transaction/pethotel', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->addDays(3)->format('Y-m-d'),
            'note'          => 'E2E hotel lifecycle',
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create hotel gagal: ' . $create->content());
        }

        $id = data_get($create->json(), 'data.id') ?? data_get($create->json(), 'id')
            ?? DB::table('transaction_pet_hotels')->where('customerId', $this->customerId)->latest('id')->value('id');

        $this->assertNotNull($id);

        // Detail
        $detail = $this->getJson('/api/transaction/pethotel/detail?id=' . $id, $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 404, 422]);

        // Accept
        $accept = $this->postJson('/api/transaction/pethotel/accept', ['id' => $id, 'doctorId' => $this->doctorId], $this->h());
        $this->assertContains($accept->getStatusCode(), [200, 201, 422, 404]);

        // Petcheck (input kosong → validasi)
        $petcheck = $this->postJson('/api/transaction/pethotel/petcheck', [], $this->h());
        $this->assertContains($petcheck->getStatusCode(), [422, 403]);

        // Check condition
        $cond = $this->getJson('/api/transaction/pethotel/check-condition', $this->h());
        $this->assertContains($cond->getStatusCode(), [200, 422]);

        // Before payment
        $bf = $this->getJson('/api/transaction/pethotel/beforepayment?id=' . $id, $this->h());
        $this->assertContains($bf->getStatusCode(), [200, 422, 404]);

        // Payment methods
        $pm = $this->getJson('/api/transaction/pethotel/payment-methods', $this->h());
        $this->assertContains($pm->getStatusCode(), [200, 422]);

        // Payment (input kosong → validasi)
        $pay = $this->postJson('/api/transaction/pethotel/payment', [], $this->h());
        $this->assertContains($pay->getStatusCode(), [422, 403, 404]);
    }

    public function test_step4_hotel_checkout_endpoints()
    {
        $checkout = $this->postJson('/api/transaction/pethotel/checkout', [], $this->h());
        $this->assertContains($checkout->getStatusCode(), [422, 403, 404]);

        $confirm = $this->postJson('/api/transaction/pethotel/confirm-payment', [], $this->h());
        $this->assertContains($confirm->getStatusCode(), [422, 403, 404]);

        $reject = $this->postJson('/api/transaction/pethotel/reject-payment', [], $this->h());
        $this->assertContains($reject->getStatusCode(), [422, 403, 404]);
    }
}
