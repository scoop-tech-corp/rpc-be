<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_pet_hotel_additional_treatments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->enum('type', ['service', 'product']);
            $table->unsignedBigInteger('itemId');
            $table->string('itemName');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('userId');
            $table->timestamps();

            $table->foreign('transactionId', 'tph_add_treat_trans_fk')->references('id')->on('transaction_pet_hotels')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_pet_hotel_additional_treatments');
    }
};
