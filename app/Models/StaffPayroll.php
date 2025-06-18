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
        'payroll_date',
        'locationId',
        'basic_income',
        'annual_increment_incentive',
        'attendance_allowance',
        'meal_allowance',
        'positional_allowance',
        'lab_xray_incentive_amount',
        'lab_xray_incentive_unit_nominal',
        'lab_xray_incentive_total',
        'grooming_incentive_amount',
        'grooming_incentive_unit_nominal',
        'grooming_incentive_total',
        'clinic_turnover_bonus',
        'replacement_days_amount',
        'replacement_days_unit_nominal',
        'replacement_days_total',
        'bpjs_health_allowance',
        'absent_amount',
        'absent_unit_nominal',
        'absent_total',
        'not_wearing_attribute_amount',
        'not_wearing_attribute_unit_nominal',
        'not_wearing_attribute_total',
        'late_amount',
        'late_unit_nominal',
        'late_total',
        'current_month_cash_advance',
        'remaining_debt_last_month',
        'stock_opname_inventory',
        'total_income',
        'total_deduction',
        'net_pay',
        'userId'
    ];


    protected $casts = [
        'payroll_date' => 'date',
    ];
}
