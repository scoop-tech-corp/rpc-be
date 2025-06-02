<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionBreedingCheck extends Model
{
    protected $table = "transaction_breeding_checks";

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
        'isRecomendInpatient',
        'noteInpatient',
        'isParent',
        'isBreastfeeding',
        'numberofChildren',
        'isAcceptToProcess',
        'reasonReject',
        'userId',
        'userUpdateId'
    ];
}
