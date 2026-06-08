<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `transaction_pet_hotel_additional_treatments`
            MODIFY COLUMN `type` ENUM('service','product','petshop','petsell','clinic')
            NOT NULL DEFAULT 'service'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `transaction_pet_hotel_additional_treatments`
            MODIFY COLUMN `type` ENUM('service','product')
            NOT NULL DEFAULT 'service'");
    }
};
