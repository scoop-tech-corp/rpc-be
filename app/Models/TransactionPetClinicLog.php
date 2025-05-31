<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicLog extends Model
{
    protected $table = "transaction_pet_clinic_logs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionId',
        'activity',
        'remark',
        'userId',
        'userUpdateId'
    ];
}
