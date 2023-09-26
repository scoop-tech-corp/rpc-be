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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string("fullName");
            $table->string("simpleName")->nullable();
            $table->string("color")->nullable();
            $table->integer("status");
            $table->integer("type");
            $table->integer("policy")->nullable();
            $table->integer("surcharges")->nullable();
            $table->integer("staffPerBooking")->nullable();
            $table->string('introduction')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('optionPolicy1')->nullable();
            $table->boolean('optionPolicy2')->nullable();
            $table->boolean('optionPolicy3')->nullable();

            $table->integer('userId');
            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userUpdateId')->nullable();
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
        Schema::dropIfExists('services');
    }
};
