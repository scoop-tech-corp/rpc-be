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
        Schema::dropIfExists('productSellReminders');
        Schema::dropIfExists('productClinicReminders');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('productSellReminders', function (Blueprint $table) {
            $table->id();

            $table->integer('productSellId');

            $table->integer('unit');
            $table->string('timing');
            $table->string('status');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        Schema::create('productClinicReminders', function (Blueprint $table) {
            $table->id();

            $table->integer('productClinicId');

            $table->integer('unit');
            $table->string('timing');
            $table->string('status');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }
};
