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
        Schema::create('facility_unit', function (Blueprint $table) {
            $table->id();
            $table->string('locationId');
            $table->string('locationName');
            $table->string('unitName');
            $table->boolean('status');
            $table->integer('capacity');
            $table->integer('amount');
            $table->string('notes')->nullable()->default(NULL);;
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
        Schema::dropIfExists('facility_unit');
    }
};
