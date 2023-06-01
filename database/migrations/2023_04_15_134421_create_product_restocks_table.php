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
        Schema::create('productRestocks', function (Blueprint $table) {
            $table->id();

            $table->string('numberId');
            $table->string('locationId');
            $table->integer('variantProduct');
            $table->integer('totalProduct');
            $table->string('supplierName');
            // $table->string('purchaseRequestNumber')->nullable();
            // $table->string('purchaseOrderNumber')->nullable();

            $table->string('status')->nullable();

            $table->integer('userIdOffice')->nullable();
            $table->integer('isApprovedOffice')->nullable()->default(0);
            $table->string('reasonOffice')->nullable();
            $table->timestamp('officeApprovedAt', 0)->nullable();

            $table->boolean('isAdminApproval');

            $table->integer('userIdAdmin')->nullable();
            $table->integer('isApprovedAdmin')->nullable()->default(0);
            $table->string('reasonAdmin')->nullable();
            $table->timestamp('adminApprovedAt', 0)->nullable();

            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->boolean('isDeleted')->nullable()->default(false);
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt', 0)->nullable();
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
        Schema::dropIfExists('productRestocks');
    }
};
