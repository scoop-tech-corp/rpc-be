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
        $data = DB::table('productSells as ps')
            ->join('productBrands as pb', 'pb.id', 'ps.productBrandId')
            ->join('productSellLocations as psl', 'psl.productSellId', 'ps.id')
            ->join('location as l', 'l.id', 'psl.locationId')
            ->select('ps.id', 'ps.fullName', 'l.locationName', 'pb.brandName')
            ->where('ps.isDeleted', '=', 0)
            ->orderBy('ps.id', 'desc')
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Produk', 'Nama Produk','Nama Lokasi', 'Nama Merk'],
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
        ];
    }
}
