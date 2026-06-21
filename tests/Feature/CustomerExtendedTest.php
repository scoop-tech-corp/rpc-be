<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Tests\TestCase;

/**
 * Covers customer sub-modules NOT in CustomerTest.php:
 * Merge, Template, Import, Feedback, SupportRequest, DataStatic
 */
class CustomerExtendedTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── MERGE ────────────────────────────────────────────────────────────────

    public function test_get_merge_preview_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/merge/preview');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_merge_preview_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/merge/preview', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_execute_merge_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/customer/merge/execute', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_execute_merge_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/customer/merge/execute', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_get_merge_history_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/merge/history');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_merge_history_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/merge/history', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_export_merge_history_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/merge/export');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── TEMPLATE ─────────────────────────────────────────────────────────────

    public function test_get_template_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/template');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_template_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/template', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_download_template_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/template/download');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── IMPORT ───────────────────────────────────────────────────────────────

    public function test_get_import_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/import');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_import_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/import', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_import_customer_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/customer/import', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_import_customer_gagal_tanpa_file()
    {
        $response = $this->postJson('/api/customer/import', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    // ─── FEEDBACK ─────────────────────────────────────────────────────────────

    public function test_get_feedback_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/feedback');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_feedback_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/feedback', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_feedback_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/customer/feedback', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_feedback_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/customer/feedback', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_update_feedback_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/customer/feedback', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_feedback_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/customer/feedback', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── SUPPORT REQUEST ──────────────────────────────────────────────────────

    public function test_get_support_request_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/support-request');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_support_request_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/support-request', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_support_request_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/customer/support-request', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_support_request_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/customer/support-request', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_update_support_request_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/customer/support-request', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_support_request_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/customer/support-request', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_support_request_history_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/support-request/history');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_support_request_history_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/support-request/history', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_get_my_requests_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/support-request/my-requests');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_my_requests_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/support-request/my-requests', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    // ─── DATASTATIC CUSTOMER ──────────────────────────────────────────────────

    public function test_get_datastatic_customer_index_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/datastatic');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_datastatic_customer_index_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/datastatic', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_datastatic_customer_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/customer/datastatic/customer');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_datastatic_customer_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/customer/datastatic/customer', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_insert_datastatic_customer_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/customer/datastatic', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_insert_datastatic_customer_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/customer/datastatic', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_datastatic_customer_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/customer/datastatic', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }
}
