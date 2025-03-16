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
        Schema::dropIfExists('productSells');
        Schema::dropIfExists('productClinics');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('productSells', function (Blueprint $table) {
            $table->id();
            $table->string('fullName');
            $table->string('simpleName')->nullable();
            $table->string('sku')->nullable();
            $table->integer('productBrandId')->nullable();
            $table->integer('productSupplierId')->nullable();
            $table->boolean('status');
            $table->date('expiredDate')->nullable();

            $table->string('pricingStatus');
            $table->decimal('costPrice', $precision = 18, $scale = 2);
            $table->decimal('marketPrice', $precision = 18, $scale = 2);
            $table->decimal('price', $precision = 18, $scale = 2);

            $table->boolean('isShipped')->nullable()->default(false);
            $table->decimal('weight', $precision = 18, $scale = 2);
            $table->decimal('length', $precision = 18, $scale = 2);
            $table->decimal('width', $precision = 18, $scale = 2);
            $table->decimal('height', $precision = 18, $scale = 2);

            $table->string('introduction')->nullable();
            $table->string('description')->nullable();

            $table->boolean('isCustomerPurchase');
            $table->boolean('isCustomerPurchaseOnline');
            $table->boolean('isCustomerPurchaseOutStock');
            $table->boolean('isStockLevelCheck');
            $table->boolean('isNonChargeable');
            $table->boolean('isOfficeApproval');
            $table->boolean('isAdminApproval');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt',0)->nullable();
            $table->timestamps();
        });

        Schema::create('productClinics', function (Blueprint $table) {
            $table->id();
            $table->string('fullName');
            $table->string('simpleName')->nullable();
            $table->string('sku')->nullable();
            $table->integer('productBrandId')->nullable();
            $table->integer('productSupplierId')->nullable();
            $table->boolean('status');
            $table->date('expiredDate')->nullable();

            $table->string('pricingStatus');
            $table->decimal('costPrice', $precision = 18, $scale = 2);
            $table->decimal('marketPrice', $precision = 18, $scale = 2);
            $table->decimal('price', $precision = 18, $scale = 2);

            $table->boolean('isShipped')->nullable()->default(false);
            $table->decimal('weight', $precision = 18, $scale = 2);
            $table->decimal('length', $precision = 18, $scale = 2);
            $table->decimal('width', $precision = 18, $scale = 2);
            $table->decimal('height', $precision = 18, $scale = 2);

            $table->string('introduction')->nullable();
            $table->string('description')->nullable();

            $table->boolean('isCustomerPurchase');
            $table->boolean('isCustomerPurchaseOnline');
            $table->boolean('isCustomerPurchaseOutStock');
            $table->boolean('isStockLevelCheck');
            $table->boolean('isNonChargeable');
            $table->boolean('isOfficeApproval');
            $table->boolean('isAdminApproval');

            $table->boolean('isDeleted')->nullable()->default(false);
            $table->integer('userId');
            $table->integer('userUpdateId')->nullable();
            $table->string('deletedBy')->nullable();
            $table->timestamp('deletedAt',0)->nullable();
            $table->timestamps();
        });
    }
};
