<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionInstallmentSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('planId');

            $table->unsignedTinyInteger('installmentNo')->comment('Urutan ke-1, 2, 3...');
            $table->date('dueDate')->comment('Jatuh tempo angsuran ini');

            $table->decimal('scheduledAmount', 18, 2)->comment('Nominal yang harus dibayar');
            $table->decimal('paidAmount',      18, 2)->default(0)->comment('Total sudah dibayar (partial OK)');
            $table->decimal('lateFeeCharged',  18, 2)->default(0)->comment('Total denda yang dikenakan');
            $table->decimal('lateFeesPaid',    18, 2)->default(0)->comment('Total denda yang sudah dibayar');

            $table->enum('status', ['unpaid', 'partial', 'paid', 'overdue'])->default('unpaid');
            $table->timestamps();

            $table->index('planId');
            $table->index('dueDate');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_installment_schedules');
    }
}
