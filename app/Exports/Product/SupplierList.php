<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class SupplierList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('productSuppliers')
            ->select('id', 'supplierName')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Supplier', 'Nama Supplier'],
        ];
    }

    public function title(): string
    {
        return 'Data Supplier';
    }

    public function map($listOfSupplier): array
    {
        return [
            $listOfSupplier->id,
            $listOfSupplier->supplierName
        ];
    }
}
