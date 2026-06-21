<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── GET /api/dashboard/overview ─────────────────────────────────────────

    public function test_dashboard_overview_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/overview')->assertStatus(401);
    }

    public function test_dashboard_overview_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/overview', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/dashboard/upbookingclinic ──────────────────────────────────

    public function test_dashboard_upcoming_booking_clinic_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/upbookingclinic')->assertStatus(401);
    }

    public function test_dashboard_upcoming_booking_clinic_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/upbookingclinic', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/dashboard/upbookinghotel ───────────────────────────────────

    public function test_dashboard_upcoming_booking_hotel_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/upbookinghotel')->assertStatus(401);
    }

    public function test_dashboard_upcoming_booking_hotel_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/upbookinghotel', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/dashboard/upbookingsalon ───────────────────────────────────

    public function test_dashboard_upcoming_booking_salon_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/upbookingsalon')->assertStatus(401);
    }

    public function test_dashboard_upcoming_booking_salon_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/upbookingsalon', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/dashboard/upbookingbreeding ────────────────────────────────

    public function test_dashboard_upcoming_booking_breeding_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/upbookingbreeding')->assertStatus(401);
    }

    public function test_dashboard_upcoming_booking_breeding_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/upbookingbreeding', $this->auth())->assertStatus(200);
    }

    // ─── GET /api/dashboard/activity ─────────────────────────────────────────

    public function test_dashboard_recent_activity_tanpa_auth_ditolak()
    {
        $this->getJson('/api/dashboard/activity')->assertStatus(401);
    }

    public function test_dashboard_recent_activity_dengan_auth_berhasil()
    {
        $this->getJson('/api/dashboard/activity', $this->auth())->assertStatus(200);
    }
}
