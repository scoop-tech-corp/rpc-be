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
        Schema::table('transaction_pet_clinic_services', function (Blueprint $table) {
            $table->integer('quantity')->nullable()->after('serviceId');
        });

        Schema::table('transaction_pet_clinic_recipes', function (Blueprint $table) {
            $table->string('notes')->nullable()->after('giveMedicine');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_pet_clinic_services', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });

        Schema::table('transaction_pet_clinic_recipes', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};
