<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotationNo')->unique();
            $table->string('status')->default('draft'); // draft | sent | accepted | rejected | expired | converted
            $table->integer('customerId');
            $table->integer('petId')->nullable();
            $table->integer('locationId');
            $table->string('typeOfService'); // clinic | hotel | salon | grooming | shop
            $table->date('validUntil');
            $table->text('notes')->nullable();
            $table->decimal('subtotalAmount', 18, 2)->default(0);
            $table->decimal('discountAmount', 18, 2)->default(0);
            $table->decimal('finalAmount', 18, 2)->default(0);
            $table->integer('convertedTransactionId')->nullable();
            $table->string('convertedTransactionType')->nullable(); // pet_clinic | pet_hotel | null
            $table->boolean('isDeleted')->default(false);
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotations');
    }
};
