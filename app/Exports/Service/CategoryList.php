<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class CategoryList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('serviceCategory')
            ->select('id', 'categoryName')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Kategori', 'Nama Kategori'],
        ];
    }

    public function title(): string
    {
        return 'Data Kategori';
    }

    public function map($listOfCategory): array
    {
        return [
            $listOfCategory->id,
            $listOfCategory->categoryName
        ];
    }
}
