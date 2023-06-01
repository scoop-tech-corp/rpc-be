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
        Schema::create('customerPets', function (Blueprint $table) {
            $table->id();
            $table->integer('customerId');
            $table->string('petName');
            $table->integer('petCategoryId');
            $table->string('races')->nullable();
            $table->string('condition');
            $table->string('color')->nullable();
            $table->enum('petGender',['J', 'B']);
            $table->integer('isSteril');
            $table->integer('petMonth')->nullable();
            $table->integer('petYear')->nullable();
            $table->date('dateOfBirth',0)->nullable();
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
        Schema::dropIfExists('customerPets');
    }
};
