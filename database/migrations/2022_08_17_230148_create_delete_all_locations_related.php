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
        

        // CREATE PROCEDURE `delete_all_location_related`(IN codeLocation VARCHAR(255)))
        // BEGIN

        //     Delete from locations where codeLocation=codeLocation;

        // END

        $procedure = "
          
            CREATE PROCEDURE `delete_all_location_related` (IN idx varchar(255))
            BEGIN
                DELETE FROM locations WHERE codeLocation = idx;
                DELETE FROM locations_alamats_details WHERE codeLocation = idx;
                DELETE FROM location_operational_hours_details WHERE codeLocation = idx;
            END;


        ";

        DB::unprepared("DROP procedure IF EXISTS delete_all_location_related");
        DB::unprepared($procedure);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      // Schema::dropIfExists('delete_all_location_related');
    }
};
