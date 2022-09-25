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
            $table->string('addressName');
            $table->string('additionalInfo');
            $table->string('cityName');
            $table->string('provinceName');
            // $table->string('districtName');
            $table->string('postalCode');
            $table->string('country');
            $table->boolean('isPrimary');
            // $table->boolean('parking');
            // $table->string('usage');    
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
