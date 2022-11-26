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
        $procedure = "
        CREATE PROCEDURE `generate_codeLocation`()
        BEGIN

            SELECT SUBSTR(MD5(RAND()), 1, 8) AS randomString;

        END
        ";

    DB::unprepared("DROP procedure IF EXISTS generate_codeLocation");
    DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('generate_codeLocation');
    }
};
