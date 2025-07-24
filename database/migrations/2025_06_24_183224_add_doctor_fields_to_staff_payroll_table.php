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
            $table->integer('patientIncentiveAmount')->default(0)->after('bpjsHealthAllowance');
            $table->integer('patientIncentiveUnitNominal')->default(0)->after('patientIncentiveAmount');
            $table->integer('patientIncentiveTotal')->default(0)->after('patientIncentiveUnitNominal');

            $table->integer('stockOpnameLost')->default(0)->after('stockOpnameInventory');
            $table->integer('stockOpnameExpired')->default(0)->after('stockOpnameLost');
        });
    }

    public function down()
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn([
                'patientIncentiveAmount',
                'patientIncentiveUnitNominal',
                'patientIncentiveTotal',
                'stockOpnameLost',
                'stockOpnameExpired',
            ]);
        });
    }
};
