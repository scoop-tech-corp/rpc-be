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
        Schema::create('productRestockDetails', function (Blueprint $table) {
            $table->id();

            $table->string('purchaseRequestNumber')->nullable();
            $table->string('purchaseOrderNumber')->nullable();

            $table->integer('productRestockId');
            $table->integer('productId');
            $table->string('productType');
            $table->integer('supplierId');
            $table->date('requireDate');
            $table->integer('currentStock');
            $table->integer('reStockQuantity');
            $table->integer('rejected');
            $table->integer('canceled');
            $table->integer('accepted');
            $table->integer('received');
            $table->decimal('costPerItem', $precision = 18, $scale = 2);
            $table->decimal('total', $precision = 18, $scale = 2);
            $table->string('remark');

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
        Schema::dropIfExists('productRestockDetails');
    }
};
