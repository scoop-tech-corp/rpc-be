<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cage_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cageId');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'selesai'])->default('pending');
            $table->integer('reportedBy');
            $table->integer('assignedTo')->nullable();
            $table->date('estimatedDone')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->text('completionNote')->nullable();
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->timestamps();

            $table->foreign('cageId')->references('id')->on('cages')->onDelete('cascade');
            $table->index(['cageId', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cage_maintenances');
    }
};
