<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer\Customer;
use App\Models\Customer\CustomerPets;

class CustomerModelTest extends TestCase
{
    // ─── Customer ────────────────────────────────────────────────────────────

    public function test_customer_table_name_is_correct()
    {
        $model = new Customer();
        $this->assertEquals('customer', $model->getTable());
    }

    public function test_customer_fillable_contains_required_fields()
    {
        $model    = new Customer();
        $fillable = $model->getFillable();

        $this->assertContains('firstName', $fillable);
        $this->assertContains('lastName', $fillable);
        $this->assertContains('locationId', $fillable);
        $this->assertContains('memberNo', $fillable);
        $this->assertContains('gender', $fillable);
        $this->assertContains('isDeleted', $fillable);
    }

    public function test_customer_dates_cast_contains_date_fields()
    {
        $model = new Customer();
        $dates = $model->getDates();

        $this->assertContains('joinDate', $dates);
        $this->assertContains('birthDate', $dates);
        $this->assertContains('created_at', $dates);
        $this->assertContains('updated_at', $dates);
    }

    public function test_customer_has_timestamps_enabled()
    {
        $model = new Customer();
        $this->assertTrue($model->timestamps);
    }

    public function test_customer_has_location_relationship()
    {
        $model = new Customer();
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->location()
        );
    }

    // ─── CustomerPets ────────────────────────────────────────────────────────

    public function test_customer_pets_table_name_is_correct()
    {
        $model = new CustomerPets();
        $this->assertEquals('customerPets', $model->getTable());
    }

    public function test_customer_pets_fillable_contains_required_fields()
    {
        $model    = new CustomerPets();
        $fillable = $model->getFillable();

        $this->assertContains('customerId', $fillable);
        $this->assertContains('petName', $fillable);
        $this->assertContains('petCategoryId', $fillable);
        $this->assertContains('petGender', $fillable);
        $this->assertContains('isDeleted', $fillable);
    }

    public function test_customer_pets_dates_contains_dateOfBirth()
    {
        $model = new CustomerPets();
        $this->assertContains('dateOfBirth', $model->getDates());
    }

    public function test_customer_pets_has_timestamps_enabled()
    {
        $model = new CustomerPets();
        $this->assertTrue($model->timestamps);
    }
}
