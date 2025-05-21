<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactionPetClinicCheckUpResults', function (Blueprint $table) {
            $table->renameColumn('isEarwax', 'earwax');
            $table->renameColumn('isPulsus', 'pulsus');
        });
    }

    public function down()
    {
        Schema::table('transactionPetClinicCheckUpResults', function (Blueprint $table) {
            $table->renameColumn('earwax', 'isEarwax');
            $table->renameColumn('pulsus', 'isPulsus');
        });
    }
};
