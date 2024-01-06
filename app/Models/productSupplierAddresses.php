<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierAddresses extends Model
{
    protected $table = "productSupplierAddresses";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productSupplierId', 'streetAddress', 'additionalInfo', 'country', 'province', 'city', 'postalCode',
        'isPrimary', 'userId', 'userUpdateId'
    ];
}
