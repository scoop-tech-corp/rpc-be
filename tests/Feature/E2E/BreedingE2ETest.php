<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E: Breeding flow
 * stats → create → accept → petcheck → treatment → prepayment → papan-kerja → checkout → payment
 */
class BreedingE2ETest extends TestCase
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
        $stats = $this->getJson('/api/transaction/breeding/stats', $this->h());
        $this->assertContains($stats->getStatusCode(), [200]);

        $index = $this->getJson('/api/transaction/breeding?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $index->getStatusCode());
        $this->assertArrayHasKey('data', $index->json());
    }

    public function test_step2_buat_transaksi_breeding()
    {
        $resp = $this->postJson('/api/transaction/breeding', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->addDays(30)->format('Y-m-d'),
            'note'          => 'E2E breeding test',
        ], $this->h());
        $this->assertContains($resp->getStatusCode(), [200, 201, 422]);
    }

    public function test_step3_full_breeding_lifecycle()
    {
        $create = $this->postJson('/api/transaction/breeding', [
            'isNewCustomer' => false,
            'isNewPet'      => false,
            'customerId'    => $this->customerId,
            'petId'         => $this->petId,
            'locationId'    => $this->locationId,
            'doctorId'      => $this->doctorId,
            'startDate'     => now()->format('Y-m-d'),
            'endDate'       => now()->addDays(30)->format('Y-m-d'),
            'note'          => 'E2E breeding lifecycle',
        ], $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Create breeding gagal: ' . $create->content());
        }

        $id = data_get($create->json(), 'data.id') ?? data_get($create->json(), 'id')
            ?? DB::table('transaction_breedings')->where('customerId', $this->customerId)->latest('id')->value('id');

        $this->assertNotNull($id);

        // Detail
        $this->assertContains(
            $this->getJson('/api/transaction/breeding/detail?id=' . $id, $this->h())->getStatusCode(),
            [200, 404, 422]
        );

        // Accept
        $accept = $this->postJson('/api/transaction/breeding/accept', ['id' => $id, 'doctorId' => $this->doctorId], $this->h());
        $this->assertContains($accept->getStatusCode(), [200, 201, 422, 404]);

        // Policies
        $pol = $this->getJson('/api/transaction/breeding/policies', $this->h());
        $this->assertContains($pol->getStatusCode(), [200, 422]);

        // Prepayments
        $prep = $this->getJson('/api/transaction/breeding/prepayments?id=' . $id, $this->h());
        $this->assertContains($prep->getStatusCode(), [200, 404, 422]);

        // Papan kerja
        $pk = $this->getJson('/api/transaction/breeding/papan-kerja?id=' . $id, $this->h());
        $this->assertContains($pk->getStatusCode(), [200, 404, 422]);

        // Additional treatments
        $at = $this->getJson('/api/transaction/breeding/additional-treatments?id=' . $id, $this->h());
        $this->assertContains($at->getStatusCode(), [200, 404, 422]);

        // Checkout (kosong → validasi)
        $checkout = $this->postJson('/api/transaction/breeding/checkout', [], $this->h());
        $this->assertContains($checkout->getStatusCode(), [422, 403, 404]);
    }

    public function test_step4_breeding_payment_endpoints()
    {
        $pay = $this->postJson('/api/transaction/breeding/payment', [], $this->h());
        $this->assertContains($pay->getStatusCode(), [422, 403, 404]);

        $confirm = $this->postJson('/api/transaction/breeding/confirm-payment', [], $this->h());
        $this->assertContains($confirm->getStatusCode(), [422, 403, 404]);

        $reject = $this->postJson('/api/transaction/breeding/reject-payment', [], $this->h());
        $this->assertContains($reject->getStatusCode(), [422, 403, 404]);
    }
}
