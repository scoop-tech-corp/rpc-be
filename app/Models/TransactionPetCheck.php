<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetCheck extends Model
{
    protected $table = "transactionPetChecks";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];


    protected $fillable = [
        'transactionId',
        'numberVaccines',
        'isLiceFree',
        'noteLiceFree',
        'isFungusFree',
        'noteFungusFree',
        'isPregnant',
        'estimateDateofBirth',
        'isParent',
        'isBreastfeeding',
        'numberofChildren',
        'isAcceptToProcess',
        'userId',
        'userUpdateId'
    ];
}
