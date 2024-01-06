<?php

namespace App\Exports\Service;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapServiceCategory implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
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

        $data = DB::table('serviceCategory as sc')->where('sc.isDeleted', '=', 0)
            ->join('users', 'sc.userId', '=', 'users.id')
            ->select('sc.id', 'sc.categoryName', 'sc.created_at', DB::raw("DATE_FORMAT(sc.created_at, '%d/%m/%Y') as createdAt"),'users.firstName as createdBy', DB::raw('(SELECT COUNT(*) FROM servicesCategoryList as scl WHERE sc.id = scl.category_id AND scl.isDeleted = 0) as totalServices'));

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
                'No.', 'Nama Kategori', 'Jumlah Service', 'Dibuat Oleh', 'Tanggal Dibuat'
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
                $item->totalServices ? $item->totalServices : "0",
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
