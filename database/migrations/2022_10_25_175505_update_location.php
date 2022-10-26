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
                        ALTER TABLE location
                        MODIFY COLUMN locationName varchar(255),
                        MODIFY COLUMN description longtext;

                        
                        ALTER TABLE location_detail_address
                        MODIFY COLUMN addressName longtext,
                        MODIFY COLUMN additionalInfo longtext;


            ";
        DB::unprepared($procedure);
 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $procedure = "
                        ALTER TABLE location
                        MODIFY COLUMN locationName varchar(255),
                        MODIFY COLUMN description longtext;

                        
                        ALTER TABLE location_detail_address
                        MODIFY COLUMN addressName longtext,
                        MODIFY COLUMN additionalInfo longtext;
            ";
            DB::unprepared($procedure);
    }
};
