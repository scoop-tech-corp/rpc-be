<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Transaction;
use App\Models\TransactionPetHotel;
use App\Models\bookings;

class TransactionModelTest extends TestCase
{
    // ─── Transaction (Klinik) ────────────────────────────────────────────────

    public function test_transaction_table_name_is_correct()
    {
        $model = new Transaction();
        $this->assertEquals('transactions', $model->getTable());
    }

    public function test_transaction_fillable_contains_required_fields()
    {
        $model    = new Transaction();
        $fillable = $model->getFillable();

        $this->assertContains('registrationNo', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('locationId', $fillable);
        $this->assertContains('customerId', $fillable);
        $this->assertContains('petId', $fillable);
        $this->assertContains('serviceCategory', $fillable);
        $this->assertContains('doctorId', $fillable);
    }

    public function test_transaction_dates_contains_created_at()
    {
        $model = new Transaction();
        $this->assertContains('created_at', $model->getDates());
    }

    // ─── TransactionPetHotel ─────────────────────────────────────────────────

    public function test_pet_hotel_transaction_table_name_is_correct()
    {
        $model = new TransactionPetHotel();
        $this->assertEquals('transaction_pet_hotels', $model->getTable());
    }

    public function test_pet_hotel_transaction_fillable_contains_required_fields()
    {
        $model    = new TransactionPetHotel();
        $fillable = $model->getFillable();

        $this->assertContains('registrationNo', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('locationId', $fillable);
        $this->assertContains('customerId', $fillable);
        $this->assertContains('petId', $fillable);
        $this->assertContains('startDate', $fillable);
        $this->assertContains('endDate', $fillable);
        $this->assertContains('stayServiceId', $fillable);
    }

    public function test_pet_hotel_has_isTreatment_field()
    {
        $model = new TransactionPetHotel();
        $this->assertContains('isTreatment', $model->getFillable());
    }

    // ─── Bookings ────────────────────────────────────────────────────────────

    public function test_bookings_table_name_is_correct()
    {
        $model = new bookings();
        $this->assertEquals('bookings', $model->getTable());
    }

    public function test_bookings_fillable_contains_required_fields()
    {
        $model    = new bookings();
        $fillable = $model->getFillable();

        $this->assertContains('locationId', $fillable);
        $this->assertContains('customerId', $fillable);
        $this->assertContains('petId', $fillable);
        $this->assertContains('serviceType', $fillable);
        $this->assertContains('bookingTime', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('isCancelled', $fillable);
    }

    public function test_bookings_has_cancellation_fields()
    {
        $model    = new bookings();
        $fillable = $model->getFillable();

        $this->assertContains('cancellationReason', $fillable);
        $this->assertContains('canceledByName', $fillable);
        $this->assertContains('cancellationDate', $fillable);
    }

    public function test_bookings_has_rejection_fields()
    {
        $model    = new bookings();
        $fillable = $model->getFillable();

        $this->assertContains('isRejected', $fillable);
        $this->assertContains('rejectionReason', $fillable);
        $this->assertContains('rejectedByName', $fillable);
    }
}
