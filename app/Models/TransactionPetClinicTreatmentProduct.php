<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicTreatmentProduct extends Model
{
    protected $table = 'transactionPetClinicTreatmentProducts';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'productId', 'quantity',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];
}
