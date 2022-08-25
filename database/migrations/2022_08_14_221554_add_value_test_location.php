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
        Schema::table('locations', function (Blueprint $table) {
           // $table->renameColumn('emp_name', 'employee_name');// Renaming "emp_name" to "employee_name"
            // $table->string('gender',10)->change(); // Change Datatype length
            //$table->dropColumn('yolo'); // Remove "active" field
          //  $table->smallInteger('yolo')->after('operational_days'); // Add "status" column
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    //    Schema::table('locations', function (Blueprint $table) {

    //         $table->dropColumn('yolo');
    //     });


        // Schema::table('locations', function (Blueprint $table) {
        //     $table->renameColumn('employee_name', 'emp_name');
        //     $table->string('gender')->change(); 
        //     $table->smallInteger('active');
        //     $table->dropColumn('status');
        // });
    }
};
