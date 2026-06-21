<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_installment_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planId');
            $table->unsignedBigInteger('scheduleId');

            $table->date('paymentDate');
            $table->decimal('amount',  18, 2)->comment('Pembayaran pokok');
            $table->decimal('lateFee', 18, 2)->default(0)->comment('Denda yang dibayar sekaligus');

            $table->unsignedBigInteger('paymentMethodId')->nullable();
            $table->string('proofOfPayment')->nullable();
            $table->string('originalName')->nullable();
            $table->string('proofRandomName')->nullable();
            $table->text('notes')->nullable();

            // Konfirmasi admin
            $table->unsignedBigInteger('confirmedBy')->nullable();
            $table->timestamp('confirmedAt')->nullable();

            $table->tinyInteger('isDeleted')->default(0);
            $table->unsignedBigInteger('userId');
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();

            $table->index('planId');
            $table->index('scheduleId');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_installment_payments');
    }
}
