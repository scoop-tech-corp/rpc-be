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
            $table->decimal('housingAllowance', 18, 2)->default(0)->after('positionalAllowance');
            $table->decimal('petshopTurnoverIncentive', 18, 2)->default(0)->after('housingAllowance');
            $table->decimal('salesAchievementBonus', 18, 2)->default(0)->after('petshopTurnoverIncentive');
            $table->decimal('memberAchievementBonus', 18, 2)->default(0)->after('salesAchievementBonus');
            $table->decimal('lostInventory', 18, 2)->default(0)->after('stockOpnameInventory');
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn([
                'housingAllowance',
                'petshopTurnoverIncentive',
                'salesAchievementBonus',
                'memberAchievementBonus',
                'lostInventory',
            ]);
        });
    }
};
