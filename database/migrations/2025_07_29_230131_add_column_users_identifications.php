<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('usersIdentifications', function (Blueprint $table) {
            $table->integer('status')->after('imagePath');
            $table->string('reason')->nullable()->after('status');
            $table->integer('approvedBy')->nullable()->after('reason');
            $table->timestamp('approvedAt')->nullable()->after('approvedBy');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usersIdentifications', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('reason');
            $table->dropColumn('approvedBy');
            $table->dropColumn('approvedAt');
        });
    }
};
