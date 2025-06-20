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
    public function up(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->date('startDate')->nullable()->after('payrollDate');
            $table->date('endDate')->nullable()->after('startDate');
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn(['startDate', 'endDate']);
        });
    }
};
