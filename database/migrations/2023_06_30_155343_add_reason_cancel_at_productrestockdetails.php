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
        Schema::table('productRestockDetails', function (Blueprint $table) {

            $table->string('reasonCancel')->nullable()->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productRestockDetails', function (Blueprint $table) {
            $table->dropColumn('reasonCancel')->nullable()->after('remark');
        });
    }
};
