<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: title "Pusat Bantuan" → "help-center" agar i18n berfungsi.
        // mappingMasterMenu memeriksa isContainsUppercaseForWord(title):
        //   - huruf kapital → dirender as-is (tidak diterjemahkan)
        //   - lowercase/kebab  → dibungkus <FormattedMessage id={title} />
        DB::table('grandChildrenMenuGroups')
            ->where('identify', 'customer-support-portal')
            ->where('title', 'Pusat Bantuan')   // idempotent: hanya update jika belum difix
            ->update(['title' => 'help-center']);
    }

    public function down(): void
    {
        DB::table('grandChildrenMenuGroups')
            ->where('identify', 'customer-support-portal')
            ->where('title', 'help-center')
            ->update(['title' => 'Pusat Bantuan']);
    }
};
