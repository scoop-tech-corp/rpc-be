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
        Schema::table('transactionPetClinics', function (Blueprint $table) {
            $table->string('nota_number')->nullable()->after('registrationNo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactionPetClinics', function (Blueprint $table) {
            $table->dropColumn('nota_number');
        });
    }
};
