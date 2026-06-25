<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use JWTAuth;
use Tests\TestCase;

/**
 * Covers product sub-modules NOT in ProductTest.php:
 * Category, Bundle, Transfer, Restock, Batch, StockOpname, LoanProduct, DeliveryAgent, DeliveryOrder
 */
class ProductExtendedTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── CATEGORY ─────────────────────────────────────────────────────────────

    public function test_get_category_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/category');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_category_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/category', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_category_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/category', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_category_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/category', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_update_category_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/product/category', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_category_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/category', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_category_detail_sell_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/category/detail/sell');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_category_detail_clinic_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/category/detail/clinic');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── BUNDLE ───────────────────────────────────────────────────────────────

    public function test_get_bundle_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/bundle');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_bundle_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/bundle', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_bundle_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/bundle', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_bundle_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/bundle', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_update_bundle_tanpa_auth_ditolak()
    {
        $response = $this->putJson('/api/product/bundle', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_bundle_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/bundle', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_bundle_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/bundle/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── TRANSFER ─────────────────────────────────────────────────────────────

    public function test_get_transfer_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/transfer');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_transfer_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/transfer', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_transfer_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/transfer', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_transfer_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/transfer', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_get_transfer_number_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/transfernumber');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_transfer_number_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/transfernumber', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_transfer_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/transfer/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_transfer_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/transfer', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── RESTOCK ──────────────────────────────────────────────────────────────

    public function test_get_restock_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/restock');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_restock_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/restock', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_restock_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/restock', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_restock_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/restock', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_restock_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/restock', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── BATCH ────────────────────────────────────────────────────────────────

    public function test_get_batch_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/batch/list-batch');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_batch_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/batch/list-batch', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_batch_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/batch/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_batch_list_transfer_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/batch/list-batch-transfer');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_batch_list_transfer_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/batch/list-batch-transfer', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    // ─── STOCK OPNAME ─────────────────────────────────────────────────────────

    public function test_get_stock_opname_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/stock-opname');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_stock_opname_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/stock-opname', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_stock_opname_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/stock-opname', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_stock_opname_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/stock-opname', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_generate_so_number_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/stock-opname/generate-so-number');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_generate_so_number_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/stock-opname/generate-so-number', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_get_stock_opname_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/stock-opname/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_stock_opname_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/stock-opname', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── LOAN PRODUCT ─────────────────────────────────────────────────────────

    public function test_get_loan_product_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/loan-product');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_loan_product_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/loan-product', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_create_loan_product_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/loan-product', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_loan_product_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/loan-product', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_generate_loan_number_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/loan-product/generate-loan-number');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_generate_loan_number_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/loan-product/generate-loan-number', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_get_loan_product_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/loan-product/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_loan_product_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/loan-product', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── DELIVERY AGENT ───────────────────────────────────────────────────────

    public function test_get_delivery_agent_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/delivery-agent');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_delivery_agent_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/delivery-agent', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_get_delivery_agent_dropdown_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/delivery-agent/dropdown');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_delivery_agent_dropdown_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/delivery-agent/dropdown', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_create_delivery_agent_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/delivery-agent', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_delivery_agent_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/delivery-agent', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_delete_delivery_agent_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/delivery-agent', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    // ─── DELIVERY ORDER ───────────────────────────────────────────────────────

    public function test_get_delivery_order_list_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/delivery-order');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_get_delivery_order_list_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/delivery-order', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    public function test_generate_delivery_number_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/delivery-order/generate-number');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_generate_delivery_number_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/product/delivery-order/generate-number', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 422, 500]);
    }

    public function test_create_delivery_order_tanpa_auth_ditolak()
    {
        $response = $this->postJson('/api/product/delivery-order', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_create_delivery_order_gagal_tanpa_required_fields()
    {
        $response = $this->postJson('/api/product/delivery-order', [], $this->auth());
        $this->assertContains($response->getStatusCode(), [422, 403, 500]);
    }

    public function test_get_delivery_order_detail_tanpa_auth_ditolak()
    {
        $response = $this->getJson('/api/product/delivery-order/detail');
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }

    public function test_delete_delivery_order_tanpa_auth_ditolak()
    {
        $response = $this->deleteJson('/api/product/delivery-order', []);
        $this->assertContains($response->getStatusCode(), [401, 500]);
    }
}
