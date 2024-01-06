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
        Schema::create('users', function (Blueprint $table) {

            $table->id();
            $table->string('firstName');
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('nickName')->nullable();
            $table->string('gender')->nullable();
            $table->boolean('status');
            $table->integer('jobTitleId');
            $table->date('startDate');
            $table->date('endDate');
            $table->string('registrationNo')->nullable();
            $table->string('designation')->nullable();
            $table->integer('annualSickAllowance')->nullable();
            $table->integer('annualLeaveAllowance')->nullable();
            $table->integer('annualSickAllowanceRemaining')->nullable();
            $table->integer('annualLeaveAllowanceRemaining')->nullable();
            $table->integer('payPeriodId');
            $table->decimal('payAmount', $precision = 18, $scale = 2)->nullable();
            $table->integer('typeId');
            $table->string('identificationNumber')->nullable();
            $table->string('additionalInfo')->nullable();   
            $table->boolean('generalCustomerCanSchedule')->nullable();
            $table->boolean('generalCustomerReceiveDailyEmail')->nullable();
            $table->boolean('generalAllowMemberToLogUsingEmail')->nullable();
            $table->boolean('reminderEmail')->nullable();
            $table->boolean('reminderWhatsapp')->nullable();
            $table->integer('roleId')->nullable();
            $table->string('password')->nullable();
            $table->string('email')->nullable();
            $table->rememberToken();
            $table->integer('isDeleted');
            $table->string('createdBy')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
