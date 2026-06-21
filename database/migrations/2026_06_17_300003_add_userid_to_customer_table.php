<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseridToCustomerTable extends Migration
{
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            // Link customer record → users account (nullable: tidak semua customer punya akun login)
            $table->unsignedBigInteger('userId')->nullable()->after('id')->comment('Link ke users.id untuk customer self-service');
            $table->index('userId');
        });
    }

    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->dropIndex(['userId']);
            $table->dropColumn('userId');
        });
    }
}
