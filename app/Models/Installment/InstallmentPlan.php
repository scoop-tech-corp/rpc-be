<?php

namespace App\Models\Installment;

use Illuminate\Database\Eloquent\Model;

class InstallmentPlan extends Model
{
    protected $table    = 'transaction_installment_plans';
    protected $fillable = [
        'transactionType', 'transactionId', 'customerId', 'locationId',
        'totalAmount', 'downPayment', 'outstandingAmount',
        'tenor', 'intervalType', 'intervalValue', 'startDate',
        'lateFeeType', 'lateFeeValue', 'lateFeeGracePeriod',
        'status', 'notes', 'isDeleted', 'userId', 'userUpdateId',
    ];

    public function schedules()
    {
        return $this->hasMany(InstallmentSchedule::class, 'planId');
    }
}
