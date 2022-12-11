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
        Schema::create('productInventories', function (Blueprint $table) {
            $table->id();

            $table->string('requirementName');
            $table->integer('locationId');
            $table->integer('totalItem');
            $table->integer('isApprovedOffice')->default(0);
            $table->integer('isApprovedAdmin')->default(0);

            $table->integer('userApproveOfficeId')->nullable();
            $table->integer('userApproveAdminId')->nullable();

            $table->timestamp('userApproveOfficeAt', 0)->nullable();
            $table->timestamp('userApproveAdminAt', 0)->nullable();

            $table->string('reasonOffice')->nullable();
            $table->string('reasonAdmin')->nullable();

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
        Schema::dropIfExists('productInventories');
    }
};
