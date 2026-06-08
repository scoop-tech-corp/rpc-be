<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelAdditionalTreatment extends Model
{
    protected $table = 'transaction_pet_hotel_additional_treatments';

    protected $fillable = [
        'transactionId',
        'type',
        'itemId',
        'itemName',
        'quantity',
        'price',
        'catatan',
        'userId',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price'    => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
