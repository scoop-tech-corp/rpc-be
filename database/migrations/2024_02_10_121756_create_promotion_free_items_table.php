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
        Schema::create('promotionFreeItems', function (Blueprint $table) {
            $table->id();

            $table->integer('promoMasterId');

            $table->integer('quantityBuyItem');
            $table->string('productBuyType');
            $table->integer('productBuyId');

            $table->integer('quantityFreeItem');
            $table->string('productFreeType');
            $table->integer('productFreeId');

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
        Schema::dropIfExists('promotionFreeItems');
    }
};
