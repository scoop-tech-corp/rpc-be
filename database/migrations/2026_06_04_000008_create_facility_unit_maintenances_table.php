<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_unit_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facilityUnitId');
            $table->foreign('facilityUnitId', 'fum_facility_unit_fk')
                  ->references('id')->on('facility_unit')->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'selesai'])->default('pending');
            $table->unsignedBigInteger('reportedBy');   // userId yang lapor
            $table->unsignedBigInteger('assignedTo')->nullable(); // userId teknisi
            $table->date('estimatedDone')->nullable();
            $table->timestamp('completedAt')->nullable();
            $table->text('completionNote')->nullable();
            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_unit_maintenances');
    }
};
