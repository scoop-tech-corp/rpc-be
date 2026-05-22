<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pushNotifications', function (Blueprint $table) {
            $table->boolean('isRead')->default(false)->after('type');
            $table->timestamp('readAt')->nullable()->after('isRead');
        });
    }

    public function down()
    {
        Schema::table('pushNotifications', function (Blueprint $table) {
            $table->dropColumn(['isRead', 'readAt']);
        });
    }
};
