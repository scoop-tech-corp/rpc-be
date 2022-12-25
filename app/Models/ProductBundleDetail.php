<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundleDetail extends Model
{
    protected $table = "productBundleDetails";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productBundleId', 'productId', 'quantity', 'total',
        'userId', 'userUpdateId'
    ];
}
