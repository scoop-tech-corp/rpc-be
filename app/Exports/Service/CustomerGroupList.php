<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class CustomerGroupList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('customerGroups')
            ->select('id', 'customerGroup')
            ->where('isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Customer Group', 'Nama Customer Group'],
        ];
    }

    public function title(): string
    {
        return 'Data Customer Group';
    }

    public function map($listOfCustomerGroup): array
    {
        return [
            $listOfCustomerGroup->id,
            $listOfCustomerGroup->customerGroup
        ];
    }
}
