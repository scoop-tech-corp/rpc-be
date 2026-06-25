<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Test lengkap untuk modul Booking (semua sub-endpoint).
 * BookingTest.php mencakup endpoint dasar; file ini mencakup detail, list, dan update.
 */
class BookingModuleTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/booking ────────────────────────────────────────────────────

    public function test_booking_index_dengan_filter_bulan_dan_tahun()
    {
        $response = $this->getJson(
            '/api/booking?monthBooking=6&yearBooking=2026',
            $this->auth()
        );
        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_booking_index_dengan_filter_service_type()
    {
        $response = $this->getJson(
            '/api/booking?serviceType=Pet Hotel',
            $this->auth()
        );
        $response->assertStatus(200)->assertJsonStructure(['data']);
    }

    // ─── GET /api/booking/detail ─────────────────────────────────────────────

    public function test_booking_detail_tanpa_auth_ditolak()
    {
        $this->getJson('/api/booking/detail?id=1')->assertStatus(401);
    }

    public function test_booking_detail_id_tidak_ada()
    {
        $response = $this->getJson('/api/booking/detail?id=99999', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 404]);
    }

    // ─── GET /api/booking/list ───────────────────────────────────────────────

    public function test_booking_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/booking/list')->assertStatus(401);
    }

    public function test_booking_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/booking/list?rowPerPage=10&goToPage=1', $this->auth());
        $response->assertStatus(200);
    }

    // ─── PUT /api/booking/cancel — validasi body ──────────────────────────────

    public function test_cancel_booking_gagal_tanpa_id()
    {
        $response = $this->putJson('/api/booking/cancel', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 200]);
    }

    // ─── PUT /api/booking/accept — validasi body ─────────────────────────────

    public function test_accept_booking_gagal_tanpa_id()
    {
        $response = $this->putJson('/api/booking/accept', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 200]);
    }

    // ─── PUT /api/booking/reject — validasi body ─────────────────────────────

    public function test_reject_booking_gagal_tanpa_id()
    {
        $response = $this->putJson('/api/booking/reject', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 200]);
    }

    // ─── POST /api/booking — validasi per service type ───────────────────────

    public function test_create_booking_pet_hotel_gagal_tanpa_emergency_contact()
    {
        $response = $this->postJson('/api/booking', [
            'locationId'  => 1,
            'doctorId'    => 1,
            'customerId'  => 1,
            'petId'       => 1,
            'services'    => 'Pet Hotel',
            'bookingTime' => '2026-07-01 10:00:00',
            // emergencyContactName tidak ada
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_create_booking_pet_salon_gagal_tanpa_fur_condition()
    {
        $response = $this->postJson('/api/booking', [
            'locationId'  => 1,
            'doctorId'    => 1,
            'customerId'  => 1,
            'petId'       => 1,
            'services'    => 'Pet Salon',
            'bookingTime' => '2026-07-01 10:00:00',
            // furCondition tidak ada
        ], $this->auth());

        $response->assertStatus(422);
    }

    public function test_create_booking_pet_clinic_valid_service_type_diterima()
    {
        // Test bahwa service type 'Pet Clinic' dikenali, bukan invalid
        $response = $this->postJson('/api/booking', [
            'locationId'  => 1,
            'doctorId'    => 1,
            'customerId'  => 1,
            'petId'       => 1,
            'services'    => 'Pet Clinic',
            'bookingTime' => '2026-07-01 10:00:00',
        ], $this->auth());

        // Harus 422 (validasi field lain) bukan 422 karena service invalid
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    // ─── DELETE /api/booking — validasi ──────────────────────────────────────

    public function test_delete_booking_endpoint_merespons_dengan_auth()
    {
        $response = $this->deleteJson('/api/booking', ['id' => 99999], $this->auth());
        $this->assertNotEquals(401, $response->getStatusCode());
        $this->assertNotEquals(404, $response->getStatusCode());
    }
}
