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
        Schema::create('location_operational', function (Blueprint $table) {
            $table->id();
            $table->string('kodeLokasi');
            $table->string('hari');
            $table->string('dariJam')->nullable()->default(NULL);
            $table->string('sampaiJam')->nullable()->default(NULL);
            $table->boolean('tiapHari');
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
        Schema::dropIfExists('location_operational');
    }
};
