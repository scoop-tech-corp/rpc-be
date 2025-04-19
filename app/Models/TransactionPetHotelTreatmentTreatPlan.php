<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelTreatmentTreatPlan extends Model
{
    protected $table = "transactionPetHotelTreatmentTreatPlans";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];


    protected $fillable = [
        'transactionId',
        'treatmentPlanId',
        'userId',
        'userUpdateId'
    ];
}
