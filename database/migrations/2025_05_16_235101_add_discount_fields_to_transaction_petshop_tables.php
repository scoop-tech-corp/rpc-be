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
    public function up(): void
    {
        Schema::table('transactionpetshopdetail', function (Blueprint $table) {
            $table->integer('discount')->default(0)->after('price');
            $table->integer('final_price')->nullable()->after('discount');
        });

        Schema::table('transactionpetshop', function (Blueprint $table) {
            $table->integer('totalAmount')->default(0)->after('note');
            $table->integer('totalDiscount')->default(0)->after('totalAmount');
            $table->integer('totalPayment')->default(0)->after('totalDiscount');
            $table->text('promoNotes')->nullable()->after('totalPayment');
        });
    }
};
