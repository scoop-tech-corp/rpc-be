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
            $table->string('codeLocation');
            $table->string('days_name');
            $table->string('from_time');
            $table->string('to_time');
            $table->boolean('all_day');
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
