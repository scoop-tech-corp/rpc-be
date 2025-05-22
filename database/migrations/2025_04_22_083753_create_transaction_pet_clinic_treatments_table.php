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
        Schema::create('transactionPetClinicTreatments', function (Blueprint $table) {
            $table->id();

            $table->boolean('isSurgery');
            $table->string('noteSurgery')->nullable();

            $table->string('infusion')->nullable();
            $table->string('fisioteraphy')->nullable();
            $table->string('injectionMedicine')->nullable();
            $table->string('oralMedicine')->nullable();
            $table->string('tropicalMedicine')->nullable();
            $table->string('vaccination')->nullable();
            $table->string('othersTreatment')->nullable();

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactionPetClinicTreatments');
    }
};
