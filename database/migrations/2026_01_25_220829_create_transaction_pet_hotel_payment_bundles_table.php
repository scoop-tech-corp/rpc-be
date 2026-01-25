<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_pet_hotel_payment_bundles', function (Blueprint $table) {
            $table->id();
            $table->integer('paymentId');
            $table->integer('promoId')->nullables();
            $table->integer('productId')->nullable();
            $table->integer('serviceId')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('amount', $precision = 18, $scale = 2);

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_pet_hotel_payment_bundles');
    }
};
