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
        Schema::table('productRestocks', function (Blueprint $table) {

            $table->dropColumn('userIdOffice');
            $table->dropColumn('isApprovedOffice');
            $table->dropColumn('reasonOffice');
            $table->dropColumn('officeApprovedAt');
            $table->dropColumn('isAdminApproval');
            $table->dropColumn('userIdAdmin');
            $table->dropColumn('isApprovedAdmin');
            $table->dropColumn('reasonAdmin');
            $table->dropColumn('adminApprovedAt');
        });

        Schema::table('productRestockDetails', function (Blueprint $table) {

            $table->integer('userIdOffice')->nullable()->after('remark');
            $table->integer('isApprovedOffice')->nullable()->default(0)->after('userIdOffice');
            $table->string('reasonOffice')->nullable()->after('isApprovedOffice');
            $table->timestamp('officeApprovedAt', 0)->nullable()->after('reasonOffice');
            $table->boolean('isAdminApproval')->after('officeApprovedAt');
            $table->integer('userIdAdmin')->nullable()->after('isAdminApproval');
            $table->integer('isApprovedAdmin')->nullable()->default(0)->after('userIdAdmin');
            $table->string('reasonAdmin')->nullable()->after('isApprovedAdmin');
            $table->timestamp('adminApprovedAt', 0)->nullable()->after('reasonAdmin');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productRestockDetails', function (Blueprint $table) {

            $table->dropColumn('userIdOffice');
            $table->dropColumn('isApprovedOffice');
            $table->dropColumn('reasonOffice');
            $table->dropColumn('officeApprovedAt');
            $table->dropColumn('isAdminApproval');
            $table->dropColumn('userIdAdmin');
            $table->dropColumn('isApprovedAdmin');
            $table->dropColumn('reasonAdmin');
            $table->dropColumn('adminApprovedAt');
        });

        Schema::table('productRestocks', function (Blueprint $table) {

            $table->integer('userIdOffice')->nullable()->after('status');
            $table->integer('isApprovedOffice')->nullable()->default(0)->after('userIdOffice');
            $table->string('reasonOffice')->nullable()->after('isApprovedOffice');
            $table->timestamp('officeApprovedAt', 0)->nullable()->after('reasonOffice');
            $table->boolean('isAdminApproval')->after('officeApprovedAt');
            $table->integer('userIdAdmin')->nullable()->after('isAdminApproval');
            $table->integer('isApprovedAdmin')->nullable()->default(0)->after('userIdAdmin');
            $table->string('reasonAdmin')->nullable()->after('isApprovedAdmin');
            $table->timestamp('adminApprovedAt', 0)->nullable()->after('reasonAdmin');
        });
    }
};
