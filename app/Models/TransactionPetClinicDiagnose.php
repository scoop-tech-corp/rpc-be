<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicDiagnose extends Model
{
    protected $table = "transactionPetClinicDiagnoses";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'diagnoseDisease',
        'prognoseDisease',
        'diseaseProgressOverview',
        'isMicroscope',
        'noteMicroscope',
        'isEye',
        'noteEye',
        'isTeskit',
        'noteTeskit',
        'isUltrasonografi',
        'noteUltrasonografi',
        'isRontgen',
        'noteRontgen',
        'isBloodReview',
        'noteBloodReview',
        'isSitologi',
        'noteSitologi',
        'isVaginalSmear',
        'noteVaginalSmear',
        'isBloodLab',
        'noteBloodLab',
        'userId',
        'userUpdateId'
    ];

}
