<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapProductCategory implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;

    public function __construct($orderValue, $orderColumn)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
    }

    public function collection()
    {

        $data = DB::table('productCategories as pc')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'categoryName',
                'pc.expiredDay as expiredDay',
                DB::raw("(select count(*) from productCoreCategories where productCategoryId=pc.id) + (select count(*) from productClinicCategories where productCategoryId=pc.id) as totalProduct"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pc.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pc.isDeleted', '=', 0);

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->get();

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Nama Kategori',
                'Hari Kedaluwarsa',
                'Jumlah Produk',
                'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Jual';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->categoryName,
                strval($item->expiredDay),
                strval($item->totalProduct),
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
