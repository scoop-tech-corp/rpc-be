<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelCheckout extends Model
{
    protected $table = 'transaction_pet_hotel_checkouts';

    protected $fillable = [
        'transactionId',
        'checkoutDate',
        'daysStayed',
        'cageId',
        'pricePerDay',
        'subtotalStay',
        'subtotalTreatment',
        'subtotalAdditional',
        'totalPrepaid',
        'subtotalBeforeDiscount',
        'discountAmount',
        'discountNote',
        'grandTotal',
        'userId',
    ];

    protected $casts = [
        'pricePerDay'            => 'float',
        'subtotalStay'           => 'float',
        'subtotalTreatment'      => 'float',
        'subtotalAdditional'     => 'float',
        'totalPrepaid'           => 'float',
        'subtotalBeforeDiscount' => 'float',
        'discountAmount'         => 'float',
        'grandTotal'             => 'float',
        'checkoutDate'           => 'date',
    ];
}
