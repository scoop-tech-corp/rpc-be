<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cage_cleaning_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cageId');
            $table->enum('cleaningStatus', ['bersih', 'perlu_pembersihan_ulang', 'dilewati']);
            $table->timestamp('cleanedAt');
            $table->string('catatan', 500)->nullable();
            $table->integer('userId');
            $table->timestamps();

            $table->foreign('cageId')->references('id')->on('cages')->onDelete('cascade');
            $table->index(['cageId', 'cleanedAt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cage_cleaning_logs');
    }
};
