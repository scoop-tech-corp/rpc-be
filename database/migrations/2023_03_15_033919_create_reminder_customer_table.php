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
        Schema::create('reminderCustomer', function (Blueprint $table) {
            $table->id();
            $table->integer('usersId');
            $table->integer('sourceId');
            $table->integer('unit');
            $table->timestamp('time',0);
            $table->date('timeDate');
            $table->enum('type',['B', 'P', 'LP']);  // B = Booking, P = Payment, LP = Late Payment
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
        Schema::dropIfExists('reminderCustomer');
    }
};
