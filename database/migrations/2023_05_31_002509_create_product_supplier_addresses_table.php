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
        Schema::create('productSupplierAddresses', function (Blueprint $table) {
            $table->id();

            $table->integer('productSupplierId');
            $table->string('streetAddress');
            $table->string('additionalInfo');
            $table->string('country');
            $table->integer('province');
            $table->integer('city');
            $table->string('postalCode');
            $table->boolean('isPrimary');

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
        Schema::dropIfExists('productSupplierAddresses');
    }
};
