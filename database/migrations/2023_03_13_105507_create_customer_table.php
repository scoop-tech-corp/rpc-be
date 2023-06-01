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
        Schema::create('customer', function (Blueprint $table) {
            $table->id();
            $table->string('firstName');
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('nickName')->nullable();
            $table->enum('gender',['P', 'W']);
            $table->integer('titleCustomerId')->nullable();
            $table->integer('customerGroupId')->nullable();
            $table->integer('locationId')->nullable();
            $table->string('notes')->nullable();
            $table->date('joinDate');
            $table->integer('typeId')->nullable();
            $table->string('numberId')->nullable();
            $table->integer('occupationId')->nullable();
            $table->date('birthDate')->nullable();
            $table->integer('referenceCustomerId')->nullable();
            $table->boolean('isReminderBooking')->nullable();
            $table->boolean('isReminderPayment')->nullable();
            $table->boolean('isDeleted')->nullable()->default(false);
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt',0)->nullable();
            $table->string('createdBy')->nullable();
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
        Schema::dropIfExists('customer');
    }
};
