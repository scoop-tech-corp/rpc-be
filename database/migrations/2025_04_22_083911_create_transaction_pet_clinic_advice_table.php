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
        Schema::create('transactionPetClinicAdvice', function (Blueprint $table) {
            $table->id();

            $table->boolean('isInpatient');
            $table->string('noteInpatient')->nullable();

            $table->boolean('isTherapeuticFeed');
            $table->string('noteTherapeuticFeed')->nullable();

            $table->string('imuneBooster')->nullable();
            $table->string('suplement')->nullable();
            $table->string('desinfeksi')->nullable();
            $table->string('care')->nullable();

            $table->boolean('isGrooming');
            $table->string('noteGrooming')->nullable();

            $table->string('othersNoteAdvice')->nullable();

            $table->date('nextControlCheckup')->nullable();

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
        Schema::dropIfExists('transactionPetClinicAdvice');
    }
};
