<?php

namespace App\Models;

use App\Models\User;
use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

        'longShiftReplacementAmount',
        'longShiftReplacementUnitNominal',
        'longShiftReplacementTotal',

        'fullShiftReplacementAmount',
        'fullShiftReplacementUnitNominal',
        'fullShiftReplacementTotal',

        'patientIncentiveAmount',
        'patientIncentiveUnitNominal',
        'patientIncentiveTotal',

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
        'stockOpnameLost',
        'stockOpnameExpired',
        'entertainAllowance',
        'transportAllowance',
        'housingAllowance',
        'turnoverAchievementBonus',

        'totalIncome',
        'totalDeduction',
        'netPay',
        'userId',
        'startDate',
        'endDate',

        'functionalLeaderAllowance',
        'hardshipAllowance',
        'familyAllowance',

        'bonusGroomingAchievement',
        'bonusSalesAchievement',
    ];

    protected $casts = [
        'payroll_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'staffId');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffId');
    }
}
