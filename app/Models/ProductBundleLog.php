<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBundleLog extends Model
{
    protected $table = "productBundleLogs";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productBundleId', 'event', 'details', 'quantity', 'total',
        'userId', 'userUpdateId'
    ];
}
