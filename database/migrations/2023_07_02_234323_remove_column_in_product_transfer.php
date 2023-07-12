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
        Schema::table('productTransfers', function (Blueprint $table) {

            $table->dropColumn('additionalCost');
            $table->dropColumn('productIdOrigin');
            $table->dropColumn('productIdDestination');
            $table->dropColumn('groupData');
            $table->dropColumn('productType');
            $table->dropColumn('totalItem');
            $table->dropColumn('remark');

            $table->dropColumn('isuserReceived');
            $table->dropColumn('receivedAt');
            $table->dropColumn('reference');
            $table->dropColumn('realImageName');
            $table->dropColumn('imagePath');
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

        Schema::table('productTransfers', function (Blueprint $table) {

            $table->string('numberId')->after('id');
            $table->integer('locationIdOrigin')->after('transferName');
            $table->integer('locationIdDestination')->after('locationIdOrigin');
            $table->integer('variantProduct')->after('locationIdDestination');
            $table->integer('totalProduct')->after('variantProduct');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('productTransfers', function (Blueprint $table) {

            $table->integer('productIdOrigin');
            $table->integer('productIdDestination');
            $table->string('groupData');
            $table->string('productType');
            $table->integer('totalItem');
            $table->string('remark');
            $table->decimal('additionalCost', $precision = 18, $scale = 2);

            $table->boolean('isUserReceived')->nullable()->default(false);
            $table->timestamp('receivedAt', 0)->nullable();
            $table->string('reference')->nullable();

            $table->string('realImageName')->nullable();
            $table->string('imagePath')->nullable();

            $table->integer('userIdOffice')->nullable();
            $table->integer('isApprovedOffice')->nullable()->default(0);
            $table->string('reasonOffice')->nullable();
            $table->timestamp('officeApprovedAt', 0)->nullable();

            $table->boolean('isAdminApproval');

            $table->integer('userIdAdmin')->nullable();
            $table->integer('isApprovedAdmin')->nullable()->default(0);
            $table->string('reasonAdmin')->nullable();
            $table->timestamp('adminApprovedAt', 0)->nullable();
        });

        Schema::table('productTransfers', function (Blueprint $table) {
            $table->dropColumn('numberId')->after('id');
            $table->dropColumn('locationIdOrigin')->after('transferName');
            $table->dropColumn('locationIdDestination')->after('locationIdDestination');
            $table->dropColumn('variantProduct')->after('locationIdDestination');
            $table->dropColumn('totalProduct')->after('variantProduct');
        });
    }
};
