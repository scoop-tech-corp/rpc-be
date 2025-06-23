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
        Schema::create('transaction_breedings', function (Blueprint $table) {
            $table->id();

            $table->string('registrationNo');
            $table->string('petCheckRegistrationNo');
            $table->string('status');
            $table->boolean('isNewCustomer');
            $table->boolean('isNewPet');
            $table->integer('locationId');
            $table->integer('customerId');
            $table->integer('petId');
            $table->string('registrant')->nullable()->default(false);

            $table->date('startDate')->nullable();
            $table->date('endDate')->nullable();
            $table->integer('doctorId');

            $table->string('note');

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
        Schema::dropIfExists('transaction_breedings');
    }
};
