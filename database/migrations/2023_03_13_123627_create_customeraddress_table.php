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
        Schema::create('customerAddress', function (Blueprint $table) {
            $table->id();
            $table->integer('usersId');
            $table->string('addressName');
            $table->string('additionalInfo')->nullable();
            $table->string('country');
            $table->integer('provinceCode');
            $table->integer('cityCode');
            $table->string('postalCode')->nullable();
            $table->boolean('isPrimary');
            $table->boolean('isDeleted')->nullable()->default(false);
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt',0)->nullable();
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
        Schema::dropIfExists('customeraddress');
    }
};
