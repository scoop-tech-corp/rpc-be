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
            $table->integer('longShiftReplacementAmount')->default(0)->after('replacementDaysTotal');
            $table->decimal('longShiftReplacementUnitNominal', 18, 2)->default(0)->after('longShiftReplacementAmount');
            $table->decimal('longShiftReplacementTotal', 18, 2)->default(0)->after('longShiftReplacementUnitNominal');

            $table->integer('fullShiftReplacementAmount')->default(0)->after('longShiftReplacementTotal');
            $table->decimal('fullShiftReplacementUnitNominal', 18, 2)->default(0)->after('fullShiftReplacementAmount');
            $table->decimal('fullShiftReplacementTotal', 18, 2)->default(0)->after('fullShiftReplacementUnitNominal');
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn([
                'longShiftReplacementAmount',
                'longShiftReplacementUnitNominal',
                'longShiftReplacementTotal',
                'fullShiftReplacementAmount',
                'fullShiftReplacementUnitNominal',
                'fullShiftReplacementTotal',
            ]);
        });
    }
};
