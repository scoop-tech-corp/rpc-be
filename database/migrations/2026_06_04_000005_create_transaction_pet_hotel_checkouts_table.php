<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_pet_hotel_checkouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId')->unique();
            $table->date('checkoutDate');
            $table->unsignedInteger('daysStayed')->default(1);
            $table->unsignedBigInteger('cageId')->nullable();
            $table->decimal('pricePerDay', 15, 2)->default(0);
            $table->decimal('subtotalStay', 15, 2)->default(0);
            $table->decimal('subtotalTreatment', 15, 2)->default(0);
            $table->decimal('subtotalAdditional', 15, 2)->default(0);
            $table->decimal('totalPrepaid', 15, 2)->default(0);
            $table->decimal('subtotalBeforeDiscount', 15, 2)->default(0);
            $table->decimal('discountAmount', 15, 2)->default(0);
            $table->string('discountNote')->nullable();
            $table->decimal('grandTotal', 15, 2)->default(0);
            $table->unsignedBigInteger('userId');
            $table->timestamps();

            $table->foreign('transactionId')->references('id')->on('transaction_pet_hotels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_pet_hotel_checkouts');
    }
};
