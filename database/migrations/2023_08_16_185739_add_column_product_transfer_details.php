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

            $table->integer('rejected')->nullable()->default(0)->after('additionalCost');
            $table->integer('canceled')->nullable()->default(0)->after('rejected');
            $table->integer('accepted')->nullable()->default(0)->after('canceled');
            $table->integer('received')->nullable()->default(0)->after('accepted');
            $table->string('reasonCancel')->nullable()->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('productTransferDetails', 'rejected')) {
            Schema::table('productTransferDetails', function (Blueprint $table) {
                $table->dropColumn('rejected');
            });
        }

        if (Schema::hasColumn('productTransferDetails', 'canceled')) {
            Schema::table('productTransferDetails', function (Blueprint $table) {
                $table->dropColumn('canceled');
            });
        }

        if (Schema::hasColumn('productTransferDetails', 'accepted')) {
            Schema::table('productTransferDetails', function (Blueprint $table) {
                $table->dropColumn('accepted');
            });
        }

        if (Schema::hasColumn('productTransferDetails', 'received')) {
            Schema::table('productTransferDetails', function (Blueprint $table) {
                $table->dropColumn('received');
            });
        }

        if (Schema::hasColumn('productTransferDetails', 'reasonCancel')) {
            Schema::table('productTransferDetails', function (Blueprint $table) {
                $table->dropColumn('reasonCancel');
            });
        }
    }
};
