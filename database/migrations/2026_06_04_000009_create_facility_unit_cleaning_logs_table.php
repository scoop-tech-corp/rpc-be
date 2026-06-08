<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_unit_cleaning_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facilityUnitId');
            $table->foreign('facilityUnitId', 'fucl_facility_unit_fk')
                  ->references('id')->on('facility_unit')->onDelete('cascade');

            $table->enum('cleaningStatus', ['bersih', 'perlu_pembersihan_ulang', 'dilewati']);
            $table->timestamp('cleanedAt');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('userId'); // cleaned by
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_unit_cleaning_logs');
    }
};
