<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactionPetClinicTreatmentServices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('serviceId');
            $table->integer('quantity')->default(1);
            $table->boolean('isDeleted')->default(false);
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactionPetClinicTreatmentServices');
    }
};
