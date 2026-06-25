<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refundNumber', 100)->unique()->nullable();  // REF/PC/1/2026/06/0001
            $table->string('serviceType', 50);                          // Pet Clinic, Pet Hotel, ...
            $table->string('invoiceNumber', 100);                       // original invoice / nota
            $table->unsignedBigInteger('transactionId')->nullable();    // FK ke tabel transaksi
            $table->unsignedBigInteger('customerId');
            $table->unsignedBigInteger('locationId');
            $table->unsignedBigInteger('paymentMethodId')->nullable();  // paymentMethodFinances.id
            $table->decimal('amount', 15, 2);                           // jumlah refund
            $table->text('reason');                                     // alasan refund
            $table->text('notes')->nullable();                          // catatan tambahan
            $table->tinyInteger('status')->default(1);                  // 1=approved, 0=pending
            $table->unsignedBigInteger('userId');                       // yang mencatat
            $table->unsignedBigInteger('approvedBy')->nullable();       // yang approve
            $table->tinyInteger('isDeleted')->default(0);
            $table->timestamps();

            $table->index(['invoiceNumber', 'serviceType']);
            $table->index(['customerId', 'locationId']);
            $table->index('isDeleted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_refunds');
    }
};
