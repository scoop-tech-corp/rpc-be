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
        Schema::create('customerPet', function (Blueprint $table) {
            $table->id();
            $table->integer('usersId');
            $table->string('petName');
            $table->integer('petCategoryId');
            $table->string('races')->nullable();
            $table->string('condition');
            $table->enum('petGender',['J', 'B']);
            $table->enum('isSteril',[true, false]);
            $table->integer('petAge')->default(0);
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
        Schema::dropIfExists('customerPet');
    }
};
