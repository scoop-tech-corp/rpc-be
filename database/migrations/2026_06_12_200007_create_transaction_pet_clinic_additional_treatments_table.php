<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactionPetClinicAdditionalTreatments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            // type: 'service' | 'product'
            $table->string('type', 20);
            // itemId merujuk ke services.id atau products.id tergantung type
            $table->unsignedBigInteger('itemId');
            $table->string('itemName');
            $table->decimal('itemPrice', 15, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->string('catatan')->nullable();
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
        Schema::dropIfExists('transactionPetClinicAdditionalTreatments');
    }
};
