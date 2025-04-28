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
        Schema::table('transactionPetClinicAnamnesis', function (Blueprint $table) {
            $table->string('locationId')->after('petCheckRegistrationNo');
        });

        Schema::table('transactionPetClinicCheckUpResults', function (Blueprint $table) {
            $table->string('transactionPetClinicId')->after('id');
        });

        Schema::table('transactionPetClinicDiagnoses', function (Blueprint $table) {
            $table->string('transactionPetClinicId')->after('id');
        });

        Schema::table('transactionPetClinicTreatments', function (Blueprint $table) {
            $table->string('transactionPetClinicId')->after('id');
        });

        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->string('transactionPetClinicId')->after('id');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactionPetClinicAnamnesis', function (Blueprint $table) {
            $table->dropColumn('locationId');
        });

        Schema::table('transactionPetClinicCheckUpResults', function (Blueprint $table) {
            $table->dropColumn('transactionPetClinicId');
        });

        Schema::table('transactionPetClinicDiagnoses', function (Blueprint $table) {
            $table->dropColumn('transactionPetClinicId');
        });

        Schema::table('transactionPetClinicTreatments', function (Blueprint $table) {
            $table->dropColumn('transactionPetClinicId');
        });

        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->dropColumn('transactionPetClinicId');
        });
    }
};
