<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPayroll extends Model
{
    use HasFactory;

    protected $table = 'staff_payroll';

    protected $fillable = [
        'staffId',
        'name',
        'payrollDate',
        'locationId',
        'basicIncome',
        'annualIncrementIncentive',
        'attendanceAllowance',
        'mealAllowance',
        'positionalAllowance',
        'labXrayIncentiveAmount',
        'labXrayIncentiveUnitNominal',
        'labXrayIncentiveTotal',
        'groomingIncentiveAmount',
        'groomingIncentiveUnitNominal',
        'groomingIncentiveTotal',
        'clinicTurnoverBonus',
        'replacementDaysAmount',
        'replacementDaysUnitNominal',
        'replacementDaysTotal',
        'bpjsHealthAllowance',
        'absentAmount',
        'absentUnitNominal',
        'absentTotal',
        'notWearingAttributeAmount',
        'notWearingAttributeUnitNominal',
        'notWearingAttributeTotal',
        'lateAmount',
        'lateUnitNominal',
        'lateTotal',
        'currentMonthCashAdvance',
        'remainingDebtLastMonth',
        'stockOpnameInventory',
        'totalIncome',
        'totalDeduction',
        'netPay',
        'userId',
        'startDate',
        'endDate'
    ];

    protected $casts = [
        'payroll_date' => 'date',
    ];
}
