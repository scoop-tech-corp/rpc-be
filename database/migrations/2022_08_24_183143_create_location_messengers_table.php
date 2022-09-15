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
        Schema::create('location_messenger', function (Blueprint $table) {
            $table->id();
            $table->string('kodeLokasi');
            $table->string('pemakaian');
            $table->string('namaMessenger');
            $table->string('tipe');
            $table->boolean('isDeleted');
            $table->timestamps();

            // $table->id();
            // $table->string('codeLocation');
            // $table->string('pemakaian');
            // $table->string('namaMessenger');
            // $table->string('tipe');
            // $table->boolean('isDeleted');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_messenger');
    }
};
