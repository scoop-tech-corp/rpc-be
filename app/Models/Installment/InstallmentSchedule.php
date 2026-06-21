<?php

namespace App\Models\Installment;

use Illuminate\Database\Eloquent\Model;

class InstallmentSchedule extends Model
{
    protected $table    = 'transaction_installment_schedules';
    protected $fillable = [
        'planId', 'installmentNo', 'dueDate',
        'scheduledAmount', 'paidAmount',
        'lateFeeCharged', 'lateFeesPaid', 'status',
    ];

    public function payments()
    {
        return $this->hasMany(InstallmentPayment::class, 'scheduleId');
    }
}
