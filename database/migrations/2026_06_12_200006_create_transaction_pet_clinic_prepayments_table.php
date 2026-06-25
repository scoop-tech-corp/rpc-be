<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactionPetClinicPrepayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('paymentMethodId');
            $table->decimal('amount', 15, 2);
            $table->string('catatan')->nullable();
            // Bukti pembayaran (opsional)
            $table->string('proofOfPayment')->nullable();
            $table->string('originalName')->nullable();
            $table->string('proofRandomName')->nullable();
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
        Schema::dropIfExists('transactionPetClinicPrepayments');
    }
};
