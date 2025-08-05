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
        'startDate',
        'endDate',
        'locationId',

        // Income-related
        'basicIncome',
        'annualIncrementIncentive',
        'attendanceAllowance',
        'mealAllowance',
        'positionalAllowance',
        'housingAllowance',
        'entertainAllowance',
        'transportAllowance',
        'turnoverAchievementBonus',
        'functionalLeaderAllowance',
        'hardshipAllowance',
        'familyAllowance',
        'bonusGroomingAchievement',
        'bonusSalesAchievement',
        'salesAchievementBonus',
        'memberAchievementBonus',
        'petshopTurnoverIncentive',

        // Incentives
        'labXrayIncentiveAmount',
        'labXrayIncentiveUnitNominal',
        'labXrayIncentiveTotal',
        'groomingIncentiveAmount',
        'groomingIncentiveUnitNominal',
        'groomingIncentiveTotal',
        'clinicTurnoverBonus',
        'patientIncentiveAmount',
        'patientIncentiveUnitNominal',
        'patientIncentiveTotal',

        // Replacements
        'replacementDaysAmount',
        'replacementDaysUnitNominal',
        'replacementDaysTotal',
        'longShiftReplacementAmount',
        'longShiftReplacementUnitNominal',
        'longShiftReplacementTotal',
        'fullShiftReplacementAmount',
        'fullShiftReplacementUnitNominal',
        'fullShiftReplacementTotal',

        // Penalties
        'absentAmount',
        'absentDays',
        'absentUnitNominal',
        'absentTotal',
        'notWearingAttributeAmount',
        'notWearingAttributeUnitNominal',
        'notWearingAttributeTotal',
        'lateAmount',
        'lateDays',
        'lateUnitNominal',
        'lateTotal',

        // Others
        'currentMonthCashAdvance',
        'remainingDebtLastMonth',
        'stockOpnameInventory',
        'stockOpnameLost',
        'stockOpnameExpired',
        'lostInventory',

        // Final calculation
        'totalIncome',
        'totalDeduction',
        'netPay',

        // Audit
        'userId',
        'userUpdateId',
        'deletedBy',
        'deletedAt'
    ];


    protected $casts = [
        'payroll_date' => 'date',
        'currentMonthCashAdvance' => 'float',
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
