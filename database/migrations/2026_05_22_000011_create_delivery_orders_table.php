<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveryOrders', function (Blueprint $table) {
            $table->id();
            $table->string('deliveryNumber')->unique();
            $table->integer('locationId');
            $table->integer('agentId')->nullable();
            $table->integer('customerId')->nullable();
            $table->string('customerName')->nullable();
            $table->string('customerPhone')->nullable();
            $table->text('deliveryAddress');
            $table->date('deliveryDate');
            $table->time('deliveryTime')->nullable();
            $table->timestamp('scheduledAt')->nullable();
            $table->timestamp('pickedUpAt')->nullable();
            $table->timestamp('deliveredAt')->nullable();
            $table->string('status')->default('draft'); // draft|assigned|picked_up|on_delivery|delivered|failed|cancelled
            $table->text('failedReason')->nullable();
            $table->string('cancelledReason')->nullable();
            $table->string('proofImageUrl')->nullable();
            $table->integer('orderId')->nullable();
            $table->integer('totalItems')->default(0);
            $table->decimal('totalWeight', 10, 2)->default(0);
            $table->decimal('totalAmount', 18, 2)->default(0);
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
        Schema::dropIfExists('deliveryOrders');
    }
};
