<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransfer extends Model
{
    protected $table = "productTransfers";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'numberId', 'transferNumber', 'transferName', 'locationIdOrigin', 'locationIdDestination',
        'variantProduct', 'totalProduct', 'userIdReceiver', 'status',

        'userId', 'userUpdateId'
    ];
}
