<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicTreatmentTreatPlan extends Model
{
    protected $table = 'transactionPetClinicTreatmentTreatPlans';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'treatmentPlanId',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];
}
