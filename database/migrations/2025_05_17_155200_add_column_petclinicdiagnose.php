<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactionPetClinicDiagnoses', function (Blueprint $table) {
            $table->boolean('isBloodReview')->after('noteRontgen');
            $table->string('noteBloodReview')->nullable()->after('isBloodReview');

            $table->boolean('isVaginalSmear')->after('noteSitologi');
            $table->string('noteVaginalSmear')->nullable()->after('isVaginalSmear');
        });
    }

    public function down()
    {
        Schema::table('transactionPetClinicDiagnoses', function (Blueprint $table) {
            $table->dropColumn('isBloodReview');
            $table->dropColumn('noteBloodReview');
            $table->dropColumn('isVaginalSmear');
            $table->dropColumn('noteVaginalSmear');
        });
    }
};
