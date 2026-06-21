<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionBreedingPrepaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_breeding_prepayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('paymentMethodId')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('nota_number')->nullable();
            $table->string('catatan')->nullable();
            $table->string('proofPath')->nullable();
            $table->string('proofOriginalName')->nullable();
            $table->unsignedBigInteger('userId');
            $table->boolean('isDeleted')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_breeding_prepayments');
    }
}
