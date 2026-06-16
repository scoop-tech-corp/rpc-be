<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quotationLogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotationId');
            $table->foreign('quotationId')->references('id')->on('quotations')->onDelete('cascade');
            $table->string('fromStatus')->nullable();
            $table->string('toStatus');
            $table->text('remarks')->nullable();
            $table->integer('changedBy'); // userId
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quotationLogs');
    }
};
