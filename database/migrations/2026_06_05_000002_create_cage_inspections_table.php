<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cage_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cageId');
            $table->enum('conditionResult', ['baik', 'perlu_perhatian', 'tidak_layak']);
            $table->text('findings')->nullable();
            $table->text('recommendation')->nullable();
            $table->tinyInteger('createMaintenance')->default(0);
            $table->timestamp('inspectedAt');
            $table->integer('userId');
            $table->timestamps();

            $table->foreign('cageId')->references('id')->on('cages')->onDelete('cascade');
            $table->index(['cageId', 'inspectedAt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cage_inspections');
    }
};
