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
        Schema::dropIfExists('productSellLogs');
        Schema::dropIfExists('productClinicLogs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('productSellLogs', function (Blueprint $table) {
            $table->id();

            $table->integer('productSellId');
            $table->string('transaction');
            $table->string('remark');
            $table->integer('quantity');
            $table->integer('balance');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });

        Schema::create('productClinicLogs', function (Blueprint $table) {
            $table->id();

            $table->integer('productClinicId');
            $table->string('transaction');
            $table->string('remark');
            $table->integer('quantity');
            $table->integer('balance');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }
};
