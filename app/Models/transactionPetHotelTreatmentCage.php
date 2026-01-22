<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transactionPetHotelTreatmentCage extends Model
{
    protected $table = "transactionPetHotelTreatmentCages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];


    protected $fillable = [
        'transactionId',
        'cageId',
        'userId',
        'userUpdateId'
    ];
}
