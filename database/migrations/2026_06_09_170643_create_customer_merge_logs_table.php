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
        Schema::create('customer_merge_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('sourceCustomerId');   // customer yang di-merge (duplikat)
            $table->integer('targetCustomerId');   // customer master yang menerima
            $table->string('sourceCustomerName');
            $table->string('targetCustomerName');
            $table->json('fieldOverrides')->nullable();       // field profil yang diambil dari source
            $table->json('transferredRelations')->nullable(); // relasi yang berhasil dipindah
            $table->integer('userId');
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
        Schema::dropIfExists('customer_merge_logs');
    }
};
