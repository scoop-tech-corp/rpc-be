<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionBreedingPolicyAgreementsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_breeding_policy_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->unsignedBigInteger('contractTemplateId')->nullable();
            $table->string('contractTitle')->nullable();
            $table->string('contractVersion')->nullable();
            $table->longText('signatureData')->nullable(); // base64 signature
            $table->string('signerName');
            $table->timestamp('signedAt')->nullable();
            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_breeding_policy_agreements');
    }
}
