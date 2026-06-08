<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->enum('type', ['hotel', 'breeding', 'salon', 'general'])
                  ->default('general')
                  ->after('notes');
        });
    }

    public function down()
    {
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
