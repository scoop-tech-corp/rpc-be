<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactionPetClinicPolicyAgreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('contractTemplateId');
            $table->string('contractTitle');
            $table->string('contractVersion', 20);
            $table->mediumText('signatureData')->nullable(); // base64 PNG tanda tangan owner
            $table->string('signerName');                   // nama owner yang tanda tangan
            $table->timestamp('signedAt')->nullable();
            $table->unsignedBigInteger('userId');           // kasir yang memproses
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactionPetClinicPolicyAgreements');
    }
};
