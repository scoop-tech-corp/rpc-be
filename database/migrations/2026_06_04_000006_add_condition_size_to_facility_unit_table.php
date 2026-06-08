<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->enum('conditionStatus', ['baik', 'perlu_perhatian', 'tidak_layak'])
                  ->default('baik')
                  ->after('type');
            $table->enum('size', ['S', 'M', 'L', 'XL'])
                  ->nullable()
                  ->after('conditionStatus');
        });
    }

    public function down(): void
    {
        Schema::table('facility_unit', function (Blueprint $table) {
            $table->dropColumn(['conditionStatus', 'size']);
        });
    }
};
