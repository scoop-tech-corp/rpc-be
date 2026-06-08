<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_pet_hotel_prepayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('paymentMethodId');
            $table->decimal('amount', 15, 2);
            $table->string('proofPath')->nullable();
            $table->string('proofOriginalName')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('userId');
            $table->timestamps();

            $table->foreign('transactionId')->references('id')->on('transaction_pet_hotels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_pet_hotel_prepayments');
    }
};
