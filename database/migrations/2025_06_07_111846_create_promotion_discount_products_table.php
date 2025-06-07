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
        Schema::create('promotion_discount_products', function (Blueprint $table) {
            $table->id();

            $table->integer('promoMasterId');
            $table->string('discountType'); // percentage or amount
            $table->integer('productId');

            $table->decimal('amount', 18, 2);
            $table->float('percent');

            $table->integer('totalMaxUsage');
            $table->integer('maxUsagePerCustomer');

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
        Schema::dropIfExists('promotion_discount_products');
    }
};
