<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('isAccepted')->default(false)->after('isCancelled');
            $table->string('acceptedByName')->nullable()->after('isAccepted');
            $table->date('acceptedDate')->nullable()->after('acceptedByName');

            $table->boolean('isRejected')->default(false)->after('acceptedDate');
            $table->string('rejectionReason')->nullable()->after('isRejected');
            $table->string('rejectedByName')->nullable()->after('rejectionReason');
            $table->date('rejectionDate')->nullable()->after('rejectedByName');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'isAccepted',
                'acceptedByName',
                'acceptedDate',
                'isRejected',
                'rejectionReason',
                'rejectedByName',
                'rejectionDate',
            ]);
        });
    }
};
