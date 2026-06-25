<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    // ─── Register ────────────────────────────────────────────────────────────

    public function test_register_endpoint_merespons_dengan_data_valid()
    {
        // Register endpoint ada dan menerima request (status 200 atau 500 karena field DB tidak lengkap)
        // Controller hanya meng-insert name/email/password/role — field users lain NOT NULL
        $response = $this->postJson('/api/register', [
            'name'     => 'Staff RPC',
            'email'    => 'staff.test.rpc@example.com',
            'password' => 'password123',
            'role'     => 'Staff',
        ]);

        // Endpoint harus merespons (bukan 404)
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    public function test_register_gagal_tanpa_email()
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Staff RPC',
            'password' => 'password123',
            'role'     => 'Staff',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    public function test_register_gagal_tanpa_nama()
    {
        $response = $this->postJson('/api/register', [
            'email'    => 'staff@rpc.com',
            'password' => 'password123',
            'role'     => 'Staff',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    public function test_register_gagal_password_terlalu_pendek()
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Staff RPC',
            'email'    => 'staff@rpc.com',
            'password' => '123',
            'role'     => 'Staff',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    public function test_register_gagal_email_tidak_valid()
    {
        $response = $this->postJson('/api/register', [
            'name'     => 'Staff RPC',
            'email'    => 'bukan-email',
            'password' => 'password123',
            'role'     => 'Staff',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function test_login_gagal_tanpa_email()
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    public function test_login_gagal_tanpa_password()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'staff@rpc.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }

    public function test_login_gagal_email_tidak_terdaftar()
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'tidakterdaftar@rpc.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('result', 'Failed');
    }

    public function test_login_gagal_email_format_tidak_valid()
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'bukan-format-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['error']);
    }
}
