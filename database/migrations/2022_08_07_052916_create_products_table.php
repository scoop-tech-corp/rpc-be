<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
             $table->id();
             $table->string('productCode');
             $table->string('name');
             $table->string('simpleName');
             $table->string('brand');
             $table->string('sku');
             $table->string('supplier');
             $table->string('status');
             $table->string('introduction')->nullable()->default(NULL);
             $table->string('description')->nullable()->default(NULL);
             $table->json('category')->nullable()->default(NULL);
             $table->string('image')->nullable()->default(NULL);
             $table->string('imageTitle')->nullable()->default(NULL);
             $table->boolean('isDeleted');
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
        Schema::dropIfExists('products');
    }
}