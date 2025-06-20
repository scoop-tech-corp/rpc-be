<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_payroll', function (Blueprint $table) {
            $table->id();
            $table->integer('staffId');
            $table->string('name');
            $table->date('payrollDate');
            $table->string('locationId');
            $table->bigInteger('basicIncome');
            $table->bigInteger('annualIncrementIncentive')->default(0);
            $table->integer('absentDays')->default(0);
            $table->integer('lateDays')->default(0);
            $table->bigInteger('totalIncome');
            $table->bigInteger('totalDeduction');
            $table->bigInteger('netPay');

            $table->decimal('attendanceAllowance', 18, 2)->default(0);
            $table->decimal('mealAllowance', 18, 2)->default(0);
            $table->decimal('positionalAllowance', 18, 2)->default(0);

            $table->integer('labXrayIncentiveAmount')->default(0);
            $table->decimal('labXrayIncentiveUnitNominal', 18, 2)->default(0);
            $table->decimal('labXrayIncentiveTotal', 18, 2)->default(0);

            $table->integer('groomingIncentiveAmount')->default(0);
            $table->decimal('groomingIncentiveUnitNominal', 18, 2)->default(0);
            $table->decimal('groomingIncentiveTotal', 18, 2)->default(0);

            $table->decimal('clinicTurnoverBonus', 18, 2)->default(0);

            $table->integer('replacementDaysAmount')->default(0);
            $table->decimal('replacementDaysUnitNominal', 18, 2)->default(0);
            $table->decimal('replacementDaysTotal', 18, 2)->default(0);

            $table->decimal('bpjsHealthAllowance', 18, 2)->default(0);

            $table->integer('absentAmount')->default(0);
            $table->decimal('absentUnitNominal', 18, 2)->default(0);
            $table->decimal('absentTotal', 18, 2)->default(0);

            $table->integer('notWearingAttributeAmount')->default(0);
            $table->decimal('notWearingAttributeUnitNominal', 18, 2)->default(0);
            $table->decimal('notWearingAttributeTotal', 18, 2)->default(0);

            $table->integer('lateAmount')->default(0);
            $table->decimal('lateUnitNominal', 18, 2)->default(0);
            $table->decimal('lateTotal', 18, 2)->default(0);

            $table->decimal('currentMonthCashAdvance', 18, 2)->default(0);
            $table->decimal('remainingDebtLastMonth', 18, 2)->default(0);
            $table->decimal('stockOpnameInventory', 18, 2)->default(0);

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_payroll');
    }
};
