<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicAdditionalTreatment extends Model
{
    protected $table = 'transactionPetClinicAdditionalTreatments';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'type', 'itemId', 'itemName', 'itemPrice',
        'quantity', 'catatan',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];
}
