<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sidebar reads title dari grandChildrenMenuGroups.title via mappingMasterMenu().
 * Jika title mengandung huruf kapital → ditampilkan as-is (plain text).
 * Jika semua lowercase → dijadikan <FormattedMessage id={title} /> sehingga bisa translate.
 *
 * Menu cicilan saat ini punya title='Cicilan' → tidak bisa switch bahasa.
 * Fix: ubah ke 'installment' agar FE membungkusnya dengan FormattedMessage.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('grandChildrenMenuGroups')
            ->where('url', '/finance/installment')
            ->where('title', 'Cicilan')
            ->update(['title' => 'installment', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('grandChildrenMenuGroups')
            ->where('url', '/finance/installment')
            ->where('title', 'installment')
            ->update(['title' => 'Cicilan', 'updated_at' => now()]);
    }
};
