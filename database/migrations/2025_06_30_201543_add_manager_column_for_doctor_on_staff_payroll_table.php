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
            $table->integer('functionalLeaderAllowance')->default(0);
            $table->integer('hardshipAllowance')->default(0);
            $table->integer('familyAllowance')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('staff_payroll', function (Blueprint $table) {
            $table->dropColumn([
                'functionalLeaderAllowance',
                'hardshipAllowance',
                'familyAllowance',
            ]);
        });
    }
};
