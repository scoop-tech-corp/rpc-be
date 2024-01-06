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
        Schema::create('serviceCategory', function (Blueprint $table) {
            $table->id();
            $table->string('categoryName');
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->boolean('isDeleted')->default(0);
            $table->timestamps();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt',0)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('serviceCategory');
    }
};
