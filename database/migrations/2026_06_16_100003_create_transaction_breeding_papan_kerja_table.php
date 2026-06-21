<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionBreedingPapanKerjaTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_breeding_papan_kerja', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transactionId');
            $table->string('activity');           // nama aktivitas, misal: "Monitoring Harian", "Makan Pagi"
            $table->date('scheduledDate');
            $table->string('time')->nullable();   // jam aktivitas
            $table->text('instructions')->nullable(); // petunjuk JSON
            $table->boolean('isDone')->default(false);
            $table->string('statusAktivitas')->nullable(); // normal, perlu perhatian, dll
            $table->text('temuan')->nullable();   // catatan dokter/vetnurse
            $table->string('catatan')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedBigInteger('completedBy')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_breeding_papan_kerja');
    }
}
