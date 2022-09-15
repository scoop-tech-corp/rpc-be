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
        Schema::create('location_alamat_detail', function (Blueprint $table) {
            $table->id();
            $table->string('kodeLokasi');
            $table->string('alamatJalan');
            $table->string('infoTambahan');
            $table->string('namaKota');
            $table->string('namaProvinsi');
            $table->string('namaKecamatan');
            $table->string('kodePos');
            $table->string('negara');
            $table->string('parkir');
            $table->boolean('isDeleted');
            $table->string('pemakaian');
            $table->timestamps();

            // $table->id();
            // $table->string('codeLocation');
            // $table->string('alamatJalan');
            // $table->string('infoTambahan');
            // $table->string('kotaID');
            // $table->string('provinsiID');
            // $table->string('kodePos');
            // $table->string('negara');
            // $table->string('parkir');
            // $table->string('isDeleted');
            // $table->string('pemakaian');
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
        Schema::dropIfExists('location_alamat_detail');
    }
};
