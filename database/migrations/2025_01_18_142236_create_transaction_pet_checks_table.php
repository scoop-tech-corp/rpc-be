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
        Schema::create('transactionPetChecks', function (Blueprint $table) {
            $table->id();

            $table->integer('transactionId');
            $table->integer('numberVaccines');
            $table->boolean('isLiceFree');
            $table->string('noteLiceFree')->nullable();
            $table->boolean('isFungusFree');
            $table->string('noteFungusFree')->nullable();
            $table->boolean('isPregnant');
            $table->date('estimateDateofBirth')->nullable();
            $table->boolean('isParent');
            $table->boolean('isBreastfeeding');
            $table->integer('numberofChildren');
            $table->boolean('isAcceptToProcess');

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
        Schema::dropIfExists('transactionPetChecks');
    }
};
