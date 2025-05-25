<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicAdvice extends Model
{
    protected $table = "transactionPetClinicAdvice";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'inpatient',
        'noteInpatient',
        'therapeuticFeed',
        'noteTherapeuticFeed',
        'imuneBooster',
        'suplement',
        'desinfeksi',
        'care',
        'grooming',
        'noteGrooming',
        'othersNoteAdvice',
        'nextControlCheckup',
        'userId',
        'userUpdateId'
    ];
}
