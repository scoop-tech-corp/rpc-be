<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSupportRequestHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_support_request_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supportRequestId');
            $table->string('fromStatus', 20)->nullable()->comment('Status sebelum perubahan');
            $table->string('toStatus', 20)->comment('Status sesudah perubahan');
            $table->unsignedBigInteger('changedBy')->nullable()->comment('users.id yang mengubah');
            $table->text('notes')->nullable()->comment('Catatan perubahan');
            $table->timestamps();

            $table->index('supportRequestId');
            $table->index('changedBy');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_support_request_histories');
    }
}
