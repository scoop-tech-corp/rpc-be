<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionInstallmentPlansTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_installment_plans', function (Blueprint $table) {
            $table->id();

            // Link ke transaksi asal
            $table->string('transactionType')->comment('Pet Clinic|Pet Hotel|Pet Salon|Breeding|Pet Shop');
            $table->unsignedBigInteger('transactionId');

            // Denormalisasi untuk kemudahan query
            $table->unsignedBigInteger('customerId');
            $table->unsignedBigInteger('locationId')->nullable();

            // Nominal
            $table->decimal('totalAmount',    18, 2)->comment('Total tagihan');
            $table->decimal('downPayment',    18, 2)->default(0)->comment('DP yang sudah dibayar di awal');
            $table->decimal('outstandingAmount', 18, 2)->comment('Sisa hutang (auto-update)');

            // Jadwal cicilan
            $table->unsignedTinyInteger('tenor')->comment('Jumlah angsuran');
            $table->enum('intervalType', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->unsignedTinyInteger('intervalValue')->default(1)->comment('Misal: setiap 2 minggu = intervalValue=2');
            $table->date('startDate')->comment('Tanggal cicilan pertama');

            // Kebijakan denda
            $table->enum('lateFeeType', ['fixed', 'percent'])->nullable()->comment('fixed=Rp/hari, percent=%sisa/hari');
            $table->decimal('lateFeeValue',   10, 2)->default(0);
            $table->unsignedTinyInteger('lateFeeGracePeriod')->default(0)->comment('Hari toleransi sebelum denda');

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->text('notes')->nullable();

            $table->tinyInteger('isDeleted')->default(0);
            $table->unsignedBigInteger('userId');
            $table->unsignedBigInteger('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();

            $table->index(['transactionType', 'transactionId'], 'tip_type_id_index');
            $table->index('customerId');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_installment_plans');
    }
}
