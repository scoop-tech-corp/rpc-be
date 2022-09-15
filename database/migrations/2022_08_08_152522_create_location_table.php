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
        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->string('kodeLokasi');
            $table->string('namaLokasi');
            $table->boolean('branch');
            $table->boolean('status');
            $table->string('introduksi')->nullable()->default(NULL);
            $table->string('deskripsi')->nullable()->default(NULL);
            $table->string('gambar')->nullable()->default(NULL);
            $table->string('judulGambar')->nullable()->default(NULL);
            $table->boolean('isDeleted');
            $table->timestamps();

            //remark temporary by danny
            // $table->id();
            // $table->string('codeLocation');
            // $table->string('locationName');
            // $table->boolean('isBranch');
            // $table->boolean('status');
            // $table->string('introduction')->nullable()->default(NULL);;
            // $table->string('description')->nullable()->default(NULL);;
            // $table->string('image')->nullable()->default(NULL);;
            // $table->string('imageTitle')->nullable()->default(NULL);;
            // $table->boolean('isDeleted');
            // $table->timestamps();
            //end remark temporary by danny
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location');
    }
};
