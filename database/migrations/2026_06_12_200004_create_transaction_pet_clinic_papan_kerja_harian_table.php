<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactionPetClinicPapanKerjaHarian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            // Nama aktivitas perawatan (dari treatmentsItems atau custom)
            $table->string('activityName');
            $table->string('activityNote')->nullable();
            // Tanggal aktivitas dijadwalkan
            $table->date('tanggal');
            // Status: pending | done | skip
            $table->string('status')->default('pending');
            $table->string('doneNote')->nullable();
            $table->unsignedBigInteger('doneBy')->nullable();   // userId yang mark done
            $table->timestamp('doneAt')->nullable();
            $table->boolean('isDeleted')->default(false);
            $table->unsignedBigInteger('userId');               // yang generate
            $table->unsignedBigInteger('userUpdateId')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactionPetClinicPapanKerjaHarian');
    }
};
