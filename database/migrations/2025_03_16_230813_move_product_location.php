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
        Schema::dropIfExists('productSellLocations');
        Schema::dropIfExists('productClinicLocations');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('productSellLocations', function (Blueprint $table) {
            $table->id();

            $table->integer('productSellId');
            $table->integer('locationId');
            $table->integer('inStock');
            $table->integer('lowStock');
            $table->integer('reStockLimit');
            $table->integer('diffStock');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        Schema::create('productClinicLocations', function (Blueprint $table) {
            $table->id();

            $table->integer('productClinicId');
            $table->integer('locationId');
            $table->integer('inStock');
            $table->integer('lowStock');
            $table->integer('reStockLimit');
            $table->integer('diffStock');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }
};
