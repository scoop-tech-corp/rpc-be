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
            $table->dropColumn('nota_number');
            $table->dropColumn('proofOfPayment');
            $table->dropColumn('originalName');
            $table->dropColumn('proofRandomName');
        });

        Schema::table('transaction_pet_clinic_payment_totals', function (Blueprint $table) {
            $table->string('nota_number')->nullable()->after('amountPaid');
            $table->string('proofOfPayment')->nullable()->after('nota_number');
            $table->string('originalName')->nullable()->after('proofOfPayment');
            $table->string('proofRandomName')->nullable()->after('originalName');
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
            $table->string('nota_number')->nullable()->after('registrationNo');
            $table->string('proofOfPayment')->nullable()->after('note');
            $table->string('originalName')->nullable()->after('proofOfPayment');
            $table->string('proofRandomName')->nullable()->after('originalName');
        });

        Schema::table('transaction_pet_clinic_payment_totals', function (Blueprint $table) {
            $table->dropColumn('nota_number');
            $table->dropColumn('proofOfPayment');
            $table->dropColumn('originalName');
            $table->dropColumn('proofRandomName');
        });
    }
};
