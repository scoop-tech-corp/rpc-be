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
            $table->integer('entertainAllowance')->default(0)->after('housingAllowance');
            $table->integer('transportAllowance')->default(0)->after('entertainAllowance');
            $table->integer('turnoverAchievementBonus')->default(0)->after('transportAllowance');
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn('entertainAllowance');
            $table->dropColumn('transportAllowance');
            $table->dropColumn('turnoverAchievementBonus');
        });
    }
};
