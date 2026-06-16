<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotationItems', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotationId');
            $table->foreign('quotationId')->references('id')->on('quotations')->onDelete('cascade');
            $table->string('itemType'); // service | product
            $table->integer('serviceId')->nullable();
            $table->integer('productId')->nullable();
            $table->string('itemName'); // snapshot nama saat quotation dibuat
            $table->integer('quantity')->default(1);
            $table->decimal('unitPrice', 18, 2)->default(0); // snapshot harga saat dibuat
            $table->decimal('totalPrice', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotationItems');
    }
};
