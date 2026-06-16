<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaction_pet_salon_policy_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('contractTemplateId');
            $table->string('contractTitle');
            $table->string('contractVersion', 20);
            $table->mediumText('signatureData')->nullable(); // base64 PNG tanda tangan customer
            $table->string('signerName');                   // nama customer yang tanda tangan
            $table->timestamp('signedAt')->nullable();
            $table->unsignedBigInteger('userId');           // kasir yang memproses
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_pet_salon_policy_agreements');
    }
};
