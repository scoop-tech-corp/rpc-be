<?php

namespace App\Exports\product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class ProductSaleList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('products as ps')
            ->join('productBrands as pb', 'pb.id', 'ps.productBrandId')
            ->join('productLocations as psl', 'psl.productId', 'ps.id')
            ->join('location as l', 'l.id', 'psl.locationId')
            ->select(
                'ps.id',
                'ps.fullName',
                'l.locationName',
                'pb.brandName',
                DB::RAW('(CASE WHEN ps.isAdminApproval = 0 THEN "Tidak" WHEN ps.isAdminApproval = 1 THEN "Ya" END) as isAdminApproval'),
                DB::RAW('(CASE WHEN ps.isOfficeApproval = 0 THEN "Tidak" WHEN ps.isOfficeApproval = 1 THEN "Ya" END) as isOfficeApproval')
            )
            ->where('ps.isDeleted', '=', 0)
            ->where('ps.category', '=', 'sell')
            ->orderBy('ps.id', 'desc')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Produk', 'Nama Produk', 'Lokasi', 'Merk', 'Persetujuan Admin', 'Persetujuan Office'],
        ];
    }

    public function title(): string
    {
        return 'Data Produk Jual';
    }

    public function map($list): array
    {
        return [
            $list->id,
            $list->fullName,
            $list->locationName,
            $list->brandName,
            $list->isAdminApproval,
            $list->isOfficeApproval,
        ];
    }
}
