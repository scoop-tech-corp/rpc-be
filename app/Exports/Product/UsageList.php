<?php

namespace App\Exports\product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class UsageList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('usages')
            ->select('id', 'usage')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Penggunaan', 'Nama Kegunaan'],
        ];
    }

    public function title(): string
    {
        return 'Data Penggunaan';
    }

    public function map($list): array
    {
        return [
            $list->id,
            $list->usage
        ];
    }
}
