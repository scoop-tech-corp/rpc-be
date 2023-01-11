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
        Schema::create('productInventoryLists', function (Blueprint $table) {
            $table->id();

            $table->integer('productInventoryId');

            $table->string('productType');
            $table->integer('productId');
            $table->integer('usageId');
            $table->integer('quantity');

            $table->integer('isApprovedOffice')->default(0);
            $table->integer('isApprovedAdmin')->default(0);
            $table->integer('userApproveOfficeId')->nullable();
            $table->integer('userApproveAdminId')->nullable();
            $table->timestamp('userApproveOfficeAt', 0)->nullable();
            $table->timestamp('userApproveAdminAt', 0)->nullable();
            $table->string('reasonOffice')->nullable();
            $table->string('reasonAdmin')->nullable();

            $table->string('itemCondition')->nullable();
            $table->date('dateCondition');
            $table->boolean('isAnyImage');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
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
        Schema::dropIfExists('productInventoryLists');
    }
};
