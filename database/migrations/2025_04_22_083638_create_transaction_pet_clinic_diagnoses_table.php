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
        Schema::create('transactionPetClinicDiagnoses', function (Blueprint $table) {
            $table->id();

            $table->string('diagnoseDisease')->nullable();
            $table->string('prognoseDisease')->nullable();
            $table->string('diseaseProgressOverview')->nullable();

            $table->boolean('isMicroscope');
            $table->string('noteMicroscope')->nullable();

            $table->boolean('isEye');
            $table->string('noteEye')->nullable();

            $table->boolean('isTeskit');
            $table->string('noteTeskit')->nullable();

            $table->boolean('isUltrasonografi');
            $table->string('noteUltrasonografi')->nullable();

            $table->boolean('isRontgen');
            $table->string('noteRontgen')->nullable();

            $table->boolean('isSitologi');
            $table->string('noteSitologi')->nullable();

            $table->boolean('isBloodLab');
            $table->string('noteBloodLab')->nullable();

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
        Schema::dropIfExists('transactionPetClinicDiagnoses');
    }
};
