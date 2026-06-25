<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBreedingPrepayment extends Model
{
    protected $table = 'transaction_breeding_prepayments';

    protected $fillable = [
        'transactionId',
        'paymentMethodId',
        'amount',
        'nota_number',
        'catatan',
        'proofPath',
        'proofOriginalName',
        'userId',
        'isDeleted',
    ];

    protected $casts = [
        'amount' => 'float',
    ];
}
