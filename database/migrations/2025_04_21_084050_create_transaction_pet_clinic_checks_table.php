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
        Schema::create('transactionPetClinicAnamnesis', function (Blueprint $table) {
            $table->id();

            $table->integer('transactionPetClinicId');
            $table->string('petCheckRegistrationNo');

            //obat cacing
            $table->boolean('isAnthelmintic');
            $table->date('anthelminticDate')->nullable();
            $table->string('anthelminticBrand')->nullable();

            $table->boolean('isVaccination');
            $table->date('vaccinationDate')->nullable();
            $table->string('vaccinationBrand')->nullable();

            //obat kutu
            $table->boolean('isFleaMedicine');
            $table->date('fleaMedicineDate')->nullable();
            $table->string('fleaMedicineBrand')->nullable();

            $table->string('previousAction')->nullable();
            $table->string('othersCompalints')->nullable();

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
        Schema::dropIfExists('transactionPetClinicAnamnesis');
    }
};
