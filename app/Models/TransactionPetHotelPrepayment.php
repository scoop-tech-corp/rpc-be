<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelPrepayment extends Model
{
    protected $table = 'transaction_pet_hotel_prepayments';

    protected $fillable = [
        'transactionId',
        'paymentMethodId',
        'amount',
        'proofPath',
        'proofOriginalName',
        'catatan',
        'userId',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(\App\Models\PaymentMethod::class, 'paymentMethodId');
    }
}
