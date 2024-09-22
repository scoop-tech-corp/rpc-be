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
        Schema::create('productBatches', function (Blueprint $table) {
            $table->id();

            $table->string('batchNumber');
            $table->integer('productId');
            $table->integer('productRestockId');
            $table->integer('productTransferId');
            $table->string('transferNumber');
            $table->integer('productRestockDetailId');
            $table->string('purchaseRequestNumber');
            $table->string('purchaseOrderNumber');
            $table->date('expiredDate')->nullable();
            $table->string('sku');

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
        Schema::dropIfExists('productBatches');
    }
};
