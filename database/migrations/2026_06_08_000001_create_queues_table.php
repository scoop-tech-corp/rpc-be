<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueuesTable extends Migration
{
    public function up()
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->string('queueNumber', 20);
            $table->enum('serviceType', ['Pet Clinic', 'Pet Hotel', 'Pet Salon', 'Breeding']);
            $table->unsignedBigInteger('locationId');
            $table->unsignedBigInteger('customerId');
            $table->unsignedBigInteger('petId');
            $table->unsignedBigInteger('doctorId')->nullable();
            $table->unsignedBigInteger('bookingId')->nullable();
            $table->text('chiefComplaint')->nullable();
            $table->enum('status', ['waiting', 'called', 'in_service', 'done', 'no_show'])->default('waiting');
            $table->date('queueDate');
            $table->timestamp('calledAt')->nullable();
            $table->timestamp('startServiceAt')->nullable();
            $table->timestamp('endServiceAt')->nullable();
            $table->unsignedBigInteger('createdBy');
            $table->unsignedBigInteger('updatedBy')->nullable();
            $table->tinyInteger('isDeleted')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('queues');
    }
}
