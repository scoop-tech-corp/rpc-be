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
        Schema::table('customer', function (Blueprint $table) {
            $table->string('colorType')->nullable()->after('notes');
        });

        Schema::table('customerPets', function (Blueprint $table) {
            $table->string('remark')->nullable()->after('dateOfBirth');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->dropColumn('colorType');
        });

        Schema::table('customerPets', function (Blueprint $table) {
            $table->dropColumn('remark');
        });
    }
};
