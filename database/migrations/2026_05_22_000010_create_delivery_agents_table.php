<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveryAgents', function (Blueprint $table) {
            $table->id();
            $table->integer('locationId');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('identityNumber')->nullable();
            $table->string('vehicleType')->nullable(); // motor|mobil|sepeda|lainnya
            $table->string('vehiclePlate')->nullable();
            $table->boolean('isActive')->default(true);
            $table->text('note')->nullable();
            $table->boolean('isDeleted')->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveryAgents');
    }
};
