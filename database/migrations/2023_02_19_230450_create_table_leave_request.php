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
        Schema::create('leaveRequest', function (Blueprint $table) {
            $table->id();
            $table->integer('usersId');
            $table->string('requesterName');
            $table->string('jobtitle');
            $table->integer('locationId');
            $table->string('leaveType');
            $table->date('fromDate');
            $table->date('toDate');
            $table->integer('duration');
            $table->string('workingdays');
            $table->string('status');
            $table->string('remark');
            $table->string('approveOrRejectedBy')->nullable();
            $table->date('approveOrRejectedDate')->nullable();
            $table->string('rejectedReason')->nullable();
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
        Schema::dropIfExists('leaveRequest');
    }
};
