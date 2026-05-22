<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveryOrderLogs', function (Blueprint $table) {
            $table->id();
            $table->integer('deliveryOrderId');
            $table->string('action'); // created|updated|assigned|picked_up|on_delivery|delivered|failed|cancelled
            $table->text('description')->nullable();
            $table->integer('userId');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveryOrderLogs');
    }
};
