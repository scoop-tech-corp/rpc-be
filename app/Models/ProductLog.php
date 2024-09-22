<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLog extends Model
{
    protected $table = "productLogs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productId', 'transaction', 'remark',
        'quantity', 'balance', 'userId', 'userUpdateId'
    ];
}
