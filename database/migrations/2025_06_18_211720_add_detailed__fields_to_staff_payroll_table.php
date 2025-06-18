<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->decimal('attendance_allowance', 18, 2)->default(0);
            $table->decimal('meal_allowance', 18, 2)->default(0);
            $table->decimal('positional_allowance', 18, 2)->default(0);

            $table->integer('lab_xray_incentive_amount')->default(0);
            $table->decimal('lab_xray_incentive_unit_nominal', 18, 2)->default(0);
            $table->decimal('lab_xray_incentive_total', 18, 2)->default(0);

            $table->integer('grooming_incentive_amount')->default(0);
            $table->decimal('grooming_incentive_unit_nominal', 18, 2)->default(0);
            $table->decimal('grooming_incentive_total', 18, 2)->default(0);

            $table->decimal('clinic_turnover_bonus', 18, 2)->default(0);

            $table->integer('replacement_days_amount')->default(0);
            $table->decimal('replacement_days_unit_nominal', 18, 2)->default(0);
            $table->decimal('replacement_days_total', 18, 2)->default(0);

            $table->decimal('bpjs_health_allowance', 18, 2)->default(0);

            $table->integer('absent_amount')->default(0);
            $table->decimal('absent_unit_nominal', 18, 2)->default(0);
            $table->decimal('absent_total', 18, 2)->default(0);

            $table->integer('not_wearing_attribute_amount')->default(0);
            $table->decimal('not_wearing_attribute_unit_nominal', 18, 2)->default(0);
            $table->decimal('not_wearing_attribute_total', 18, 2)->default(0);

            $table->integer('late_amount')->default(0);
            $table->decimal('late_unit_nominal', 18, 2)->default(0);
            $table->decimal('late_total', 18, 2)->default(0);

            $table->decimal('current_month_cash_advance', 18, 2)->default(0);
            $table->decimal('remaining_debt_last_month', 18, 2)->default(0);
            $table->decimal('stock_opname_inventory', 18, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_allowance', 'meal_allowance', 'positional_allowance',
                'lab_xray_incentive_amount', 'lab_xray_incentive_unit_nominal', 'lab_xray_incentive_total',
                'grooming_incentive_amount', 'grooming_incentive_unit_nominal', 'grooming_incentive_total',
                'clinic_turnover_bonus',
                'replacement_days_amount', 'replacement_days_unit_nominal', 'replacement_days_total',
                'bpjs_health_allowance',
                'absent_amount', 'absent_unit_nominal', 'absent_total',
                'not_wearing_attribute_amount', 'not_wearing_attribute_unit_nominal', 'not_wearing_attribute_total',
                'late_amount', 'late_unit_nominal', 'late_total',
                'current_month_cash_advance', 'remaining_debt_last_month', 'stock_opname_inventory'
            ]);
        });
    }
};
