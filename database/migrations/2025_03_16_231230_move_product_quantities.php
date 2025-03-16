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
        Schema::dropIfExists('productSellQuantities');
        Schema::dropIfExists('productClinicQuantities');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('productSellQuantities', function (Blueprint $table) {
            $table->id();

            $table->integer('productSellId');
            $table->integer('fromQty');
            $table->integer('toQty');
            $table->decimal('price', $precision = 18, $scale = 2);

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        Schema::create('productClinicQuantities', function (Blueprint $table) {
            $table->id();

            $table->integer('productClinicId');
            $table->integer('fromQty');
            $table->integer('toQty');
            $table->decimal('price', $precision = 18, $scale = 2);

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }
};
