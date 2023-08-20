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
        Schema::table('productTransferDetails', function (Blueprint $table) {

            $table->integer('rejected')->nullable()->default(0)->after('additionalCost');
            $table->integer('canceled')->nullable()->default(0)->after('rejected');
            $table->integer('accepted')->nullable()->default(0)->after('canceled');
            $table->integer('received')->nullable()->default(0)->after('accepted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productTransferDetails', function (Blueprint $table) {
            $table->dropColumn('rejected')->after('additionalCost');
            $table->dropColumn('canceled')->after('rejected');
            $table->dropColumn('accepted')->after('canceled');
            $table->dropColumn('received')->after('accepted');
        });
    }
};
