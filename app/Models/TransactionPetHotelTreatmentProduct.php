<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelTreatmentProduct extends Model
{
    protected $table = "transactionPetHotelTreatmentProducts";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];


    protected $fillable = [
        'transactionId',
        'productId',
        'quantity',
        'userId',
        'userUpdateId'
    ];
}
