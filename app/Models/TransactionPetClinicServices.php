<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicServices extends Model
{
    protected $table = "transaction_pet_clinic_services";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'serviceId',
        'quantity',
        'userId',
        'userUpdateId'
    ];
}
