<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class transactionPetSalonTreatmentCage extends Model
{
    protected $table = "transactionPetSalonTreatmentCages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionId',
        'cageId',
        'userId',
        'userUpdateId',
    ];
}
