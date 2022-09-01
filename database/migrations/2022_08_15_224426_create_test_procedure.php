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
        // $procedure = "
        // CREATE PROCEDURE `procedure_name`(procedure_param_1 TEXT, procedure_param_2 TEXT)
        // BEGIN
        //         SELECT SUBSTR(MD5(RAND()), 1, 8) AS randomString;
        // END
        // ";
        

        //     DECLARE @value as varchar(255),
        //             @check_true as boolean

        //     SET @check_true=true

        //     while check_true = true
        //     begin
        //         SELECT @value=SUBSTR(MD5(RAND()), 1, 8) AS randomString;

        //         if not exists(select '' from locations where codeLocation=@value)
        //         begin
        //            SET @check_true=false
        //         end

        //     end

        //    select @value;



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
   //   Schema::dropIfExists('generate_codeLocation');
    }
};
