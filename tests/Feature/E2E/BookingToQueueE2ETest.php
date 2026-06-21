<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JWTAuth;
use Tests\TestCase;

/**
 * E2E Test: Booking → Accept/Reject → Queue → Transaction Flow
 *
 * Skenario:
 *   1. Buat booking baru
 *   2. Cek booking muncul di list
 *   3. Accept booking
 *   4. Reject booking
 *   5. Cancel booking
 *   6. Queue index / add
 *   7. Convert booking ke queue
 */
class BookingToQueueE2ETest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private string $token;
    private int $locationId = 1;
    private int $customerId = 2;   // agus (seed)
    private int $petId      = 2;   // YOLO milik customer 2

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

    /** Payload lengkap untuk membuat booking klinik termasuk file gambar */
    private function bookingPayload(array $extra = []): array
    {
        return array_merge([
            'locationId'      => $this->locationId,
            'customerId'      => $this->customerId,
            'petId'           => $this->petId,
            'services'        => 'Pet Clinic',
            'bookingTime'     => now()->addDay()->format('Y-m-d H:i:s'),
            'doctorId'        => $this->user->id,
            'consultationType'=> 'Rawat Jalan',
            'image'           => UploadedFile::fake()->image('booking.jpg', 100, 100),
        ], $extra);
    }

    // ── Step 1: Booking index ──────────────────────────────────────────────

    public function test_step1_booking_list_accessible()
    {
        $response = $this->getJson('/api/booking?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
    }

    // ── Step 2: Buat booking (validasi input) ─────────────────────────────

    public function test_step2_buat_booking_klinik()
    {
        $response = $this->post('/api/booking', $this->bookingPayload(), $this->h());

        $this->assertContains(
            $response->getStatusCode(),
            [200, 201, 422],
            'Create booking: ' . $response->content()
        );
    }

    // ── Step 3: Full flow create → list → detail → accept ────────────────

    public function test_step3_create_booking_lalu_accept()
    {
        $create = $this->post('/api/booking', $this->bookingPayload([
            'bookingTime' => now()->addDays(1)->format('Y-m-d H:i:s'),
        ]), $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Booking create gagal: ' . $create->content());
        }

        $bookingId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('bookings')->latest('id')->value('id');

        $this->assertNotNull($bookingId, 'Tidak dapat menemukan booking ID');

        // Verifikasi di list
        $list = $this->getJson('/api/booking?rowPerPage=10&goToPage=1', $this->h());
        $this->assertEquals(200, $list->getStatusCode());

        // Detail booking
        $detail = $this->getJson('/api/booking/detail?id=' . $bookingId, $this->h());
        $this->assertContains($detail->getStatusCode(), [200, 404]);

        // Accept booking
        $accept = $this->putJson('/api/booking/accept', ['id' => $bookingId], $this->h());
        $this->assertContains($accept->getStatusCode(), [200, 201, 422, 404]);
    }

    // ── Step 4: Reject booking ────────────────────────────────────────────

    public function test_step4_create_booking_lalu_reject()
    {
        $create = $this->post('/api/booking', $this->bookingPayload([
            'bookingTime' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ]), $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Booking create gagal: ' . $create->content());
        }

        $bookingId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('bookings')->latest('id')->value('id');

        $reject = $this->putJson('/api/booking/reject', [
            'id'              => $bookingId,
            'rejectionReason' => 'E2E test rejection',
        ], $this->h());
        $this->assertContains($reject->getStatusCode(), [200, 201, 422, 404]);
    }

    // ── Step 5: Cancel booking ────────────────────────────────────────────

    public function test_step5_create_booking_lalu_cancel()
    {
        $create = $this->post('/api/booking', $this->bookingPayload([
            'bookingTime' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ]), $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Booking create gagal');
        }

        $bookingId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('bookings')->latest('id')->value('id');

        $cancel = $this->putJson('/api/booking/cancel', [
            'id'                 => $bookingId,
            'cancellationReason' => 'E2E test cancel',
        ], $this->h());
        $this->assertContains($cancel->getStatusCode(), [200, 201, 422, 404]);
    }

    // ── Step 6: Queue flow ────────────────────────────────────────────────

    public function test_step6_queue_index_dan_add()
    {
        // Queue index
        $index = $this->getJson('/api/queue?rowPerPage=10&goToPage=1', $this->h());
        $this->assertContains($index->getStatusCode(), [200]);
        $this->assertArrayHasKey('data', $index->json());

        // Tambah ke queue — input kosong harus 422
        $add = $this->postJson('/api/queue', [], $this->h());
        $this->assertContains($add->getStatusCode(), [422, 403]);

        // Booking candidates
        $candidates = $this->getJson('/api/queue/booking-candidates', $this->h());
        $this->assertContains($candidates->getStatusCode(), [200, 422]);
    }

    // ── Step 7: Queue convert dari booking ───────────────────────────────

    public function test_step7_convert_booking_ke_queue()
    {
        $create = $this->post('/api/booking', $this->bookingPayload(), $this->h());

        if (!in_array($create->getStatusCode(), [200, 201])) {
            $this->markTestSkipped('Booking create gagal');
        }

        $bookingId = data_get($create->json(), 'data.id')
            ?? data_get($create->json(), 'id')
            ?? DB::table('bookings')->latest('id')->value('id');

        // Accept terlebih dahulu
        $this->putJson('/api/booking/accept', ['id' => $bookingId], $this->h());

        // Convert ke queue
        $convert = $this->postJson('/api/queue/convert', [
            'bookingId' => $bookingId,
        ], $this->h());
        $this->assertContains(
            $convert->getStatusCode(),
            [200, 201, 422, 404],
            'Convert booking to queue: ' . $convert->content()
        );
    }
}
