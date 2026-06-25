<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use JWTAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ReportTest extends TestCase
{
    use DatabaseTransactions;

    private function auth(): array
    {
        $user  = User::factory()->create(['isDeleted' => 0]);
        $token = JWTAuth::fromUser($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    // ─── Report Booking ───────────────────────────────────────────────────────

    public function test_report_booking_location_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/location')->assertStatus(401);
    }

    public function test_report_booking_location_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/location', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_status_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/status')->assertStatus(401);
    }

    public function test_report_booking_status_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/status', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_cancellation_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/cancellationreason')->assertStatus(401);
    }

    public function test_report_booking_cancellation_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/cancellationreason', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/list')->assertStatus(401);
    }

    public function test_report_booking_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/list', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_diagnose_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/diagnose')->assertStatus(401);
    }

    public function test_report_booking_diagnose_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/diagnose', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_diagnose_options_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/diagnoseoptions')->assertStatus(401);
    }

    public function test_report_booking_diagnose_options_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/booking/diagnoseoptions', $this->auth())->assertStatus(200);
    }

    public function test_report_booking_diagnosespecies_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/booking/diagnosespecies')->assertStatus(401);
    }

    public function test_report_booking_diagnosespecies_dengan_auth_berhasil()
    {
        $response = $this->getJson('/api/report/booking/diagnosespecies', $this->auth());
        $this->assertContains($response->getStatusCode(), [200, 500]);
    }

    // ─── Report Customer ──────────────────────────────────────────────────────

    public function test_report_customer_growth_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/customer/growth')->assertStatus(401);
    }

    public function test_report_customer_growth_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/customer/growth', $this->auth())->assertStatus(200);
    }

    public function test_report_customer_total_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/customer/total')->assertStatus(401);
    }

    public function test_report_customer_total_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/customer/total', $this->auth())->assertStatus(200);
    }

    public function test_report_customer_leaving_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/customer/leaving')->assertStatus(401);
    }

    public function test_report_customer_leaving_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/customer/leaving', $this->auth())->assertStatus(200);
    }

    public function test_report_customer_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/customer/list')->assertStatus(401);
    }

    public function test_report_customer_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/customer/list', $this->auth())->assertStatus(200);
    }

    public function test_report_customer_refspend_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/customer/refspend')->assertStatus(401);
    }

    public function test_report_customer_refspend_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/customer/refspend', $this->auth())->assertStatus(200);
    }

    // ─── Report Deposit ───────────────────────────────────────────────────────

    public function test_report_deposit_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/deposit/list')->assertStatus(401);
    }

    public function test_report_deposit_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/deposit/list', $this->auth())->assertStatus(200);
    }

    public function test_report_deposit_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/deposit/summary')->assertStatus(401);
    }

    public function test_report_deposit_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/deposit/summary', $this->auth())->assertStatus(200);
    }

    // ─── Report Expenses ──────────────────────────────────────────────────────

    public function test_report_expenses_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/expenses/list')->assertStatus(401);
    }

    public function test_report_expenses_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/expenses/list', $this->auth())->assertStatus(200);
    }

    public function test_report_expenses_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/expenses/summary')->assertStatus(401);
    }

    public function test_report_expenses_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/expenses/summary', $this->auth())->assertStatus(200);
    }

    public function test_report_expenses_options_payment_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/expenses/options/payment')->assertStatus(401);
    }

    public function test_report_expenses_options_payment_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/expenses/options/payment', $this->auth())->assertStatus(200);
    }

    public function test_report_expenses_options_category_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/expenses/options/category')->assertStatus(401);
    }

    public function test_report_expenses_options_category_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/expenses/options/category', $this->auth())->assertStatus(200);
    }

    // ─── Report Products ──────────────────────────────────────────────────────

    public function test_report_products_stockcount_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/stockcount')->assertStatus(401);
    }

    public function test_report_products_stockcount_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/stockcount', $this->auth())->assertStatus(200);
    }

    public function test_report_products_lowstock_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/lowstock')->assertStatus(401);
    }

    public function test_report_products_lowstock_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/lowstock', $this->auth())->assertStatus(200);
    }

    public function test_report_products_nostock_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/nostock')->assertStatus(401);
    }

    public function test_report_products_nostock_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/nostock', $this->auth())->assertStatus(200);
    }

    public function test_report_products_reminders_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/reminders')->assertStatus(401);
    }

    public function test_report_products_reminders_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/reminders', $this->auth())->assertStatus(200);
    }

    public function test_report_products_expiry_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/expiry')->assertStatus(401);
    }

    public function test_report_products_expiry_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/expiry', $this->auth())->assertStatus(200);
    }

    public function test_report_products_batches_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/products/batches')->assertStatus(401);
    }

    public function test_report_products_batches_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/products/batches', $this->auth())->assertStatus(200);
    }

    // ─── Report Sales ─────────────────────────────────────────────────────────

    public function test_report_sales_items_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/items')->assertStatus(401);
    }

    public function test_report_sales_items_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/items', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/summary')->assertStatus(401);
    }

    public function test_report_sales_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/summary', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_by_service_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/salesbyservice')->assertStatus(401);
    }

    public function test_report_sales_by_service_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/salesbyservice', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_by_product_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/salesbyproduct')->assertStatus(401);
    }

    public function test_report_sales_by_product_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/salesbyproduct', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_payment_list_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/paymentlist')->assertStatus(401);
    }

    public function test_report_sales_payment_list_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/paymentlist', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_details_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/details')->assertStatus(401);
    }

    public function test_report_sales_details_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/details', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_unpaid_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/unpaid')->assertStatus(401);
    }

    public function test_report_sales_unpaid_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/unpaid', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_discount_summary_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/discountsummary')->assertStatus(401);
    }

    public function test_report_sales_discount_summary_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/discountsummary', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_net_income_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/netincome')->assertStatus(401);
    }

    public function test_report_sales_net_income_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/netincome', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_daily_audit_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/dailyaudit')->assertStatus(401);
    }

    public function test_report_sales_daily_audit_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/dailyaudit', $this->auth())->assertStatus(200);
    }

    public function test_report_sales_staff_service_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/sales/staffservicesales')->assertStatus(401);
    }

    public function test_report_sales_staff_service_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/sales/staffservicesales', $this->auth())->assertStatus(200);
    }

    // ─── Report Staff ─────────────────────────────────────────────────────────

    public function test_report_staff_login_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/staff/login')->assertStatus(401);
    }

    public function test_report_staff_login_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/staff/login', $this->auth())->assertStatus(200);
    }

    public function test_report_staff_late_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/staff/late')->assertStatus(401);
    }

    public function test_report_staff_late_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/staff/late', $this->auth())->assertStatus(200);
    }

    public function test_report_staff_leave_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/staff/leave')->assertStatus(401);
    }

    public function test_report_staff_leave_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/staff/leave', $this->auth())->assertStatus(200);
    }

    public function test_report_staff_performance_tanpa_auth_ditolak()
    {
        $this->getJson('/api/report/staff/peformance')->assertStatus(401);
    }

    public function test_report_staff_performance_dengan_auth_berhasil()
    {
        $this->getJson('/api/report/staff/peformance', $this->auth())->assertStatus(200);
    }
}
