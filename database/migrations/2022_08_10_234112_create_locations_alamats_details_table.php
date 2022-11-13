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
        Schema::create('location_detail_address', function (Blueprint $table) {
            $table->id();
            $table->string('codeLocation');
            $table->longText('addressName')->nullable();   
            $table->longText('additionalInfo')->nullable();
            $table->integer('provinceCode')->nullable();
            $table->integer('cityCode')->nullable();
            $table->integer('postalCode')->nullable();
            $table->string('country')->nullable();
            $table->boolean('isPrimary');  
            $table->boolean('isDeleted');
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
        Schema::dropIfExists('location_detail_address');
    }
};
