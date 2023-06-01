<?php

namespace App\Exports\product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;

class ProductClinicList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('productClinics as pc')
            ->join('productBrands as pb', 'pb.id', 'pc.productBrandId')
            ->join('productClinicLocations as pcl', 'pcl.productClinicId', 'pc.id')
            ->join('location as l', 'l.id', 'pcl.locationId')
            ->select(
                'pc.id',
                'pc.fullName',
                'l.locationName',
                'pb.brandName',
                DB::RAW('(CASE WHEN pc.isAdminApproval = 0 THEN "Tidak" WHEN pc.isAdminApproval = 1 THEN "Ya" END) as isAdminApproval'),
                DB::RAW('(CASE WHEN pc.isOfficeApproval = 0 THEN "Tidak" WHEN pc.isOfficeApproval = 1 THEN "Ya" END) as isOfficeApproval')
            )
            ->where('pc.isDeleted', '=', 0)
            ->orderBy('pc.id', 'desc')
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
        return 'Data Produk Klinik';
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
