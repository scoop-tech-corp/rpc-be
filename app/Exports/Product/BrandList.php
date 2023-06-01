<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class BrandList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('productBrands')
            ->select('id', 'brandName')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Merk', 'Nama Merk'],
        ];
    }

    public function title(): string
    {
        return 'Data Merk';
    }

    public function map($listOfBrand): array
    {
        return [
            $listOfBrand->id,
            $listOfBrand->brandName
        ];
    }
}
