<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_opname_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('stockOpnameId');
            $table->string('event');
            $table->string('details');
            $table->integer('userId');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_opname_logs');
    }
};
