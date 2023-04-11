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
        Schema::create('customerReminders', function (Blueprint $table) {
            $table->id();
            $table->integer('customerId');
            $table->integer('sourceCustomerId');
            $table->integer('unit');
            $table->string('time')->nullable();
            $table->string('timeDate')->nullable();
            $table->enum('type',['B', 'P', 'LP']);  // B = Booking, P = Payment, LP = Late Payment
            $table->string('notes');
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
        Schema::dropIfExists('customerReminders');
    }
};
