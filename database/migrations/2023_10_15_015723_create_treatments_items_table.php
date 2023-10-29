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
        Schema::create('treatmentsItems', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('treatments_id');
            $table->foreign('treatments_id')->references('id')->on('treatments')->onDelete('cascade');


            $table->unsignedBigInteger('frequency_id');
            $table->foreign('frequency_id')->references('id')->on('servicesFrequency')->onDelete('cascade');


            $table->string('duration')->nullable();
            $table->string('start')->nullable();
            $table->string('product_type')->nullable();
            $table->string('product_name')->nullable();

            $table->longText('notes')->nullable();


            $table->unsignedBigInteger('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');

            $table->unsignedBigInteger('task_id')->nullable();
            $table->foreign('task_id')->references('id')->on('task')->onDelete('cascade');

            $table->integer('userId')->nullable();
            $table->boolean('isDeleted')->nullable()->default(false);
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
        Schema::dropIfExists('treatmentsItems');
    }
};
