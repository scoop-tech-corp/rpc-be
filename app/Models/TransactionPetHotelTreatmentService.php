<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelTreatmentService extends Model
{
    protected $table = "transactionPetHotelTreatmentServices";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];


    protected $fillable = [
        'transactionId',
        'serviceId',
        'quantity',
        'userId',
        'userUpdateId'
    ];
}
