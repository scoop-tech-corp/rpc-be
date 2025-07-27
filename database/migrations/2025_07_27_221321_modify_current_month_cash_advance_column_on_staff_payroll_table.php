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
            $table->dropColumn('currentMonthCashAdvance');
        });

       
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->integer('currentMonthCashAdvance')->default(0)->after('totalIncome');
        });
    }

    public function down()
    {
       
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn('currentMonthCashAdvance');
        });

        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->decimal('currentMonthCashAdvance', 15, 2)->default(0)->after('totalIncome');
        });
    }
};
