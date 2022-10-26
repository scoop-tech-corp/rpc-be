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
        DB::statement('ALTER TABLE location_detail_address MODIFY COLUMN addressName longtext,
                        MODIFY COLUMN additionalInfo longtext;
                        ');

        DB::statement('ALTER TABLE location MODIFY COLUMN description longtext;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      
        DB::statement('ALTER TABLE location_detail_address MODIFY COLUMN addressName longtext,
                       MODIFY COLUMN additionalInfo longtext;
                      ');

        DB::statement('ALTER TABLE location MODIFY COLUMN description longtext;
        ');
      
    }
};
