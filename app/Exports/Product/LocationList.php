<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class LocationList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('location')
            ->select('id', 'locationName')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Lokasi', 'Nama Lokasi'],
        ];
    }

    public function title(): string
    {
        return 'Data Lokasi';
    }

    public function map($listOfLocation): array
    {
        return [
            $listOfLocation->id,
            $listOfLocation->locationName
        ];
    }
}
