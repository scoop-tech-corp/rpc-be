<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionBreedingAdditionalTreatmentsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_breeding_additional_treatments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->string('type');       // 'service' | 'product'
            $table->unsignedBigInteger('itemId')->nullable();
            $table->string('itemName');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->boolean('isDeleted')->default(false);
            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_breeding_additional_treatments');
    }
}
