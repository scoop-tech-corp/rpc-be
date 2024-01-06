<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierPhones extends Model
{
    protected $table = "productSupplierPhones";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productSupplierId', 'usageId', 'number', 'typePhoneId', 'userId', 'userUpdateId'
    ];
}
