<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicPrepayment extends Model
{
    protected $table = 'transactionPetClinicPrepayments';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'paymentMethodId', 'amount', 'catatan',
        'proofOfPayment', 'originalName', 'proofRandomName',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];
}
