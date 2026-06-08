<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL ENUM tidak bisa langsung di-alter pakai Blueprint enum(),
        // gunakan raw SQL untuk menambah value 'maternal'.
        DB::statement("ALTER TABLE `cages` MODIFY COLUMN `type` ENUM('hotel','breeding','salon','general','maternal') NOT NULL DEFAULT 'general'");
    }

    public function down(): void
    {
        // Hapus 'maternal' — pastikan tidak ada row bertipe maternal terlebih dahulu
        DB::statement("ALTER TABLE `cages` MODIFY COLUMN `type` ENUM('hotel','breeding','salon','general') NOT NULL DEFAULT 'general'");
    }
};
