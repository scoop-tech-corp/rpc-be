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
        Schema::create('staffAbsents', function (Blueprint $table) {
            $table->id();

            $table->dateTime('presentTime');
            $table->string('longitude');
            $table->string('latitude');
            $table->integer('status');
            $table->string('reason')->nullable();
            $table->string('realImageName');
            $table->string('imagePath');
            $table->string('address');
            $table->string('city');
            $table->string('province');

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
        Schema::dropIfExists('staffAbsents');
    }
};
