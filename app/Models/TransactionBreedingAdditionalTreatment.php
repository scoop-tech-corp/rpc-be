<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBreedingAdditionalTreatment extends Model
{
    protected $table = 'transaction_breeding_additional_treatments';

    protected $fillable = [
        'transactionId',
        'type',
        'itemId',
        'itemName',
        'quantity',
        'price',
        'catatan',
        'isDeleted',
        'userId',
    ];

    protected $casts = [
        'quantity'  => 'float',
        'price'     => 'float',
        'isDeleted' => 'boolean',
    ];
}
