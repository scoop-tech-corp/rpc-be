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
        Schema::create('staff_payroll', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('payroll_date');
            $table->string('locationId');
            $table->bigInteger('basic_income');
            $table->bigInteger('annual_increment_incentive')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0); 
            $table->bigInteger('total_income');
            $table->bigInteger('total_deduction');
            $table->bigInteger('net_pay');
            
            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_payroll');
    }
};
