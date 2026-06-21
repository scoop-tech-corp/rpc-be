<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSupportRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_support_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customerId');
            $table->unsignedBigInteger('locationId')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->unsignedBigInteger('handledBy')->nullable()->comment('staffId yang menangani');
            $table->timestamp('resolvedAt')->nullable();
            $table->tinyInteger('isDeleted')->default(0);
            $table->unsignedBigInteger('deletedBy')->nullable();
            $table->timestamp('deletedAt')->nullable();
            $table->timestamps();

            $table->index('customerId');
            $table->index('locationId');
            $table->index(['created_at', 'isDeleted']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_support_requests');
    }
}
