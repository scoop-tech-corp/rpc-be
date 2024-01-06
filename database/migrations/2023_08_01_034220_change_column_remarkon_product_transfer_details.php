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
        Schema::table('productTransferDetails', function (Blueprint $table) {
            $table->string('remark')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('UPDATE productTransferDetails SET remark = "" WHERE remark IS NULL;');
        DB::statement('ALTER TABLE productTransferDetails MODIFY column remark varchar(255)');
    }
};
