<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPayroll extends Model
{
    use HasFactory;

    protected $table = 'staff_payroll';

    protected $fillable = [
        'name',
        'payroll_date',
        'locationId',
        'basic_income',
        'annual_increment_incentive',
        'absent_days',
        'late_days',
        'total_income',
        'total_deduction',
        'net_pay',
    ];

    protected $casts = [
        'payroll_date' => 'date',
    ];
}
