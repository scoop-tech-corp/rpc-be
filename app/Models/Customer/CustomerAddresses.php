<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddresses extends Model
{
    use HasFactory;

    protected $table = "customerAddresses";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'addressName',
        'additionalInfo',
        'provinceCode',
        'cityCode',
        'postalCode',
        'country',
        'isPrimary',
        'isDeleted',
        'created_at',
        'updated_at',
    ];
}
