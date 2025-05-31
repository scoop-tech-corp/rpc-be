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
        Schema::table('transactionpetshop', function (Blueprint $table) {
            $table->string('originalName')->nullable()->after('proofOfPayment');
            $table->string('proofRandomName')->nullable()->after('originalName');
        });
    }

    public function down()
    {
        Schema::table('transactionpetshop', function (Blueprint $table) {
            $table->dropColumn(['originalName', 'proofRandomName']);
        });
    }
};
