<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deliveryOrderDetails', function (Blueprint $table) {
            $table->id();
            $table->integer('deliveryOrderId');
            $table->string('productType'); // sell|clinic|product
            $table->integer('productId');
            $table->string('productName');
            $table->string('sku')->nullable();
            $table->integer('qty');
            $table->decimal('unitPrice', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('weight', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveryOrderDetails');
    }
};
