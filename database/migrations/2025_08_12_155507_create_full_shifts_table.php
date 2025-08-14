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
        Schema::create('full_shifts', function (Blueprint $table) {
            $table->id();
            $table->integer('locationId');
            $table->date('fullShiftDate');
            $table->string('reason');

            $table->integer('status');
            $table->string('reasonChecker')->nullable();
            $table->integer('approvedBy')->default(0);
            $table->timestamp('approvedAt')->nullable();

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
        Schema::dropIfExists('full_shifts');
    }
};
