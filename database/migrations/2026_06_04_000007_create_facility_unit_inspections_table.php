<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_unit_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facilityUnitId');
            $table->foreign('facilityUnitId', 'fui_facility_unit_fk')
                  ->references('id')->on('facility_unit')->onDelete('cascade');

            $table->enum('conditionResult', ['baik', 'perlu_perhatian', 'tidak_layak']);
            $table->text('findings')->nullable();       // temuan
            $table->text('recommendation')->nullable(); // rekomendasi tindakan
            $table->boolean('createMaintenance')->default(false); // langsung buat maintenance?
            $table->timestamp('inspectedAt');
            $table->unsignedBigInteger('userId');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_unit_inspections');
    }
};
