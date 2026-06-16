<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicTreatmentService extends Model
{
    protected $table = 'transactionPetClinicTreatmentServices';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'serviceId', 'quantity',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];
}
