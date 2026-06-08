<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('locationId');
            $table->string('cageName', 100);
            $table->enum('type', ['hotel', 'breeding', 'salon', 'general'])->default('general');
            $table->enum('size', ['S', 'M', 'L', 'XL'])->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=aktif, 0=nonaktif');
            $table->enum('conditionStatus', ['baik', 'perlu_perhatian', 'tidak_layak'])->default('baik');
            $table->integer('capacity')->default(1);
            $table->integer('amount')->default(1);
            $table->string('notes', 300)->nullable();
            $table->tinyInteger('isDeleted')->default(0);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();

            $table->foreign('locationId')->references('id')->on('location')->onDelete('cascade');
            $table->index(['locationId', 'isDeleted']);
            $table->index(['type', 'isDeleted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cages');
    }
};
