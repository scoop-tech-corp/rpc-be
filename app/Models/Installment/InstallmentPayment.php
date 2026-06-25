<?php

namespace App\Models\Installment;

use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    protected $table    = 'transaction_installment_payments';
    protected $fillable = [
        'planId', 'scheduleId', 'paymentDate',
        'amount', 'lateFee', 'paymentMethodId',
        'proofOfPayment', 'originalName', 'proofRandomName',
        'notes', 'confirmedBy', 'confirmedAt',
        'isDeleted', 'userId',
    ];
}
