<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaction_pet_hotel_papan_kerja', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('transactionId');
            $table->enum('type', ['harian', 'vetnurse']);
            $table->string('time', 10);
            $table->string('activity');
            $table->json('instructions')->nullable();

            $table->boolean('isDone')->default(false);
            $table->string('statusAktivitas')->nullable();
            $table->json('temuan')->nullable();
            $table->string('kondisiFeses')->nullable();
            $table->text('catatan')->nullable();
            $table->string('foto')->nullable();

            $table->unsignedBigInteger('completedBy')->nullable();
            $table->timestamp('completedAt')->nullable();

            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_pet_hotel_papan_kerja');
    }
};
