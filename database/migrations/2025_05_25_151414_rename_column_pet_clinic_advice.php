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
        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->integer('isInpatient')->change();
            $table->integer('isTherapeuticFeed')->change();
            $table->integer('isGrooming')->change();
        });

        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->renameColumn('isInpatient', 'inpatient');
            $table->renameColumn('isTherapeuticFeed', 'therapeuticFeed');
            $table->renameColumn('isGrooming', 'grooming');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->renameColumn('inpatient', 'isInpatient');
            $table->renameColumn('therapeuticFeed', 'isTherapeuticFeed');
            $table->renameColumn('grooming', 'isGrooming');
        });

        Schema::table('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->boolean('isInpatient')->change();
            $table->boolean('isTherapeuticFeed')->change();
            $table->boolean('isGrooming')->change();
        });
    }
};
