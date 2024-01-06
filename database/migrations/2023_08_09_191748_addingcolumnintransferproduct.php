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
        Schema::table('productTransfers', function (Blueprint $table) {

            $table->boolean('isAdminApproval')->after('userIdReceiver');
        });

        Schema::table('productRestocks', function (Blueprint $table) {

            $table->boolean('isAdminApproval')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productTransfers', function (Blueprint $table) {
            $table->dropColumn('isAdminApproval')->after('userIdReceiver');
        });

        Schema::table('productRestocks', function (Blueprint $table) {
            $table->dropColumn('isAdminApproval')->after('status');
        });
    }
};
