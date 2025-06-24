<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->renameColumn('basic_income', 'basicIncome');
            $table->renameColumn('annual_increment_incentive', 'annualIncrementIncentive');
            $table->renameColumn('absent_days', 'absentDays');
            $table->renameColumn('late_days', 'lateDays');
            $table->renameColumn('total_income', 'totalIncome');
            $table->renameColumn('total_deduction', 'totalDeduction');
            $table->renameColumn('net_pay', 'netPay');
        });
    }

    public function down()
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->renameColumn('basicIncome', 'basic_income');
            $table->renameColumn('annualIncrementIncentive', 'annual_increment_incentive');
            $table->renameColumn('absentDays', 'absent_days');
            $table->renameColumn('lateDays', 'late_days');
            $table->renameColumn('totalIncome', 'total_income');
            $table->renameColumn('totalDeduction', 'total_deduction');
            $table->renameColumn('netPay', 'net_pay');
        });
    }
};
