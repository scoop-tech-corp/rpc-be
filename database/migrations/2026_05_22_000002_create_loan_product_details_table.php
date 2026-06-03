<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('loanProductDetails', function (Blueprint $table) {
            $table->id();
            $table->integer('loanProductId');
            $table->string('productType'); // sell|clinic|product
            $table->integer('productId');
            $table->string('productName');
            $table->string('sku')->nullable();
            $table->integer('loanedQty');
            $table->decimal('costPrice', 18, 2)->default(0);
            $table->decimal('suggestedPrice', 18, 2)->default(0);
            $table->integer('soldQty')->default(0);
            $table->decimal('actualSellingPrice', 18, 2)->default(0);
            $table->integer('returnedQty')->default(0);
            $table->decimal('revenue', 18, 2)->default(0);
            $table->text('itemNote')->nullable();
            $table->string('returnStatus')->default('pending'); // pending|returned
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loanProductDetails');
    }
};
