<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BookingTest extends TestCase
{
    use DatabaseTransactions;

    private function getAuthHeader(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);

        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/booking ────────────────────────────────────────────────────

    public function test_get_booking_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/booking');

        // Tanpa token harus return 401
        $response->assertStatus(401);
    }

    public function test_get_booking_list_dengan_auth_berhasil()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->getJson('/api/booking', $headers);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    // ─── POST /api/booking — validasi input ──────────────────────────────────

    public function test_create_booking_gagal_tanpa_auth()
    {
        $response = $this->postJson('/api/booking', []);

        $response->assertStatus(401);
    }

    public function test_create_booking_gagal_tanpa_required_fields()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->postJson('/api/booking', [], $headers);

        // Harus return validasi error
        $response->assertStatus(422);
    }

    public function test_create_booking_gagal_serviceType_tidak_valid()
    {
        $headers  = $this->getAuthHeader();
        $response = $this->postJson('/api/booking', [
            'locationId'  => 1,
            'doctorId'    => 1,
            'customerId'  => 1,
            'petId'       => 1,
            'services'    => 'Invalid Service Type',
            'bookingTime' => '2026-07-01 10:00:00',
        ], $headers);

        $response->assertStatus(422);
    }

    // ─── PUT /api/booking/cancel — validasi ──────────────────────────────────

    public function test_cancel_booking_gagal_tanpa_auth()
    {
        $response = $this->putJson('/api/booking/cancel', ['id' => 1]);

        $response->assertStatus(401);
    }

    // ─── PUT /api/booking/accept ─────────────────────────────────────────────

    public function test_accept_booking_gagal_tanpa_auth()
    {
        $response = $this->putJson('/api/booking/accept', ['id' => 1]);

        $response->assertStatus(401);
    }

    // ─── PUT /api/booking/reject ─────────────────────────────────────────────

    public function test_reject_booking_gagal_tanpa_auth()
    {
        $response = $this->putJson('/api/booking/reject', ['id' => 1]);

        $response->assertStatus(401);
    }

    // ─── DELETE /api/booking ──────────────────────────────────────────────────

    public function test_delete_booking_gagal_tanpa_auth()
    {
        $response = $this->deleteJson('/api/booking', ['id' => 1]);

        $response->assertStatus(401);
    }
}
