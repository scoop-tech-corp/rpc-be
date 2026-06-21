<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerFeedbacksTable extends Migration
{
    public function up()
    {
        Schema::create('customer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customerId');
            $table->unsignedBigInteger('locationId')->nullable();
            $table->unsignedBigInteger('transactionId')->nullable();
            $table->string('transactionType')->nullable()->comment('Pet Clinic|Pet Hotel|Pet Salon|Breeding|Pet Shop');
            $table->tinyInteger('rating')->comment('1-5');
            $table->text('message')->nullable();
            $table->tinyInteger('isDeleted')->default(0);
            $table->unsignedBigInteger('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();

            $table->index('customerId');
            $table->index('locationId');
            $table->index(['created_at', 'isDeleted']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_feedbacks');
    }
}
