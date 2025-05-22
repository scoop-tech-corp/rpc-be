<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transactionPetClinicAnamnesis extends Model
{
    protected $table = "transactionPetClinicAnamnesis";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'petCheckRegistrationNo',
        'isAnthelmintic',
        'anthelminticDate',
        'anthelminticBrand',
        'isVaccination',
        'vaccinationDate',
        'vaccinationBrand',
        'isFleaMedicine',
        'fleaMedicineDate',
        'fleaMedicineBrand',
        'previousAction',
        'othersCompalints',
        'userId',
        'userUpdateId'
    ];
}
