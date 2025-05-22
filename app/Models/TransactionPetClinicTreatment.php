<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicTreatment extends Model
{
    protected $table = "transactionPetClinicTreatments";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'isSurgery',
        'noteSurgery',
        'infusion',
        'fisioteraphy',
        'injectionMedicine',
        'oralMedicine',
        'tropicalMedicine',
        'vaccination',
        'othersTreatment',
        'userId',
        'userUpdateId'
    ];

}
