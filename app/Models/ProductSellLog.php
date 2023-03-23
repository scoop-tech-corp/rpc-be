<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellLog extends Model
{
    protected $table = "productSellLogs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productSellId', 'transaction', 'remark',
        'quantity', 'balance', 'userId', 'userUpdateId'
    ];
}
