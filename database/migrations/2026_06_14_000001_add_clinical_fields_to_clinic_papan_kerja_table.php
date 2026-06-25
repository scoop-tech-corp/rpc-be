<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactionPetClinicPapanKerjaHarian', function (Blueprint $table) {
            // Waktu jadwal aktivitas (misal "08:00")
            $table->string('time', 10)->nullable()->after('tanggal');

            // Hasil isi form vetnurse saat mark done
            $table->string('statusAktivitas')->nullable()->after('doneNote'); // terlaksana | dilewati
            $table->string('kondisiUmum')->nullable()->after('statusAktivitas');   // baik | perlu_perhatian | kritis
            $table->string('nafsuMakan')->nullable()->after('kondisiUmum');        // normal | sedikit | tidak_makan
            $table->string('outputFeses')->nullable()->after('nafsuMakan');        // normal | diare | konstipasi | tidak_bab
            $table->string('outputUrin')->nullable()->after('outputFeses');        // normal | tidak_bak
            $table->boolean('obatDiberikan')->nullable()->after('outputUrin');
            $table->string('catatanObat')->nullable()->after('obatDiberikan');
            $table->text('catatan')->nullable()->after('catatanObat');
            $table->string('foto')->nullable()->after('catatan');
        });
    }

    public function down()
    {
        Schema::table('transactionPetClinicPapanKerjaHarian', function (Blueprint $table) {
            $table->dropColumn([
                'time', 'statusAktivitas', 'kondisiUmum', 'nafsuMakan',
                'outputFeses', 'outputUrin', 'obatDiberikan', 'catatanObat',
                'catatan', 'foto',
            ]);
        });
    }
};
