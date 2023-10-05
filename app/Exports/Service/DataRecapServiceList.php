<?php

namespace App\Exports\Service;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapServiceList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
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

            $data = DB::table('services as sc')->where('sc.isDeleted', '=', 0)
                    ->join('users', 'sc.userId', '=', 'users.id')
                    ->orderBy('sc.updated_at', 'desc')
                    ->select('sc.id', 'sc.fullName', 'sc.color', 'sc.type', 'sc.optionPolicy1', 'sc.status', 'sc.created_at', 'sc.updated_at', DB::raw("DATE_FORMAT(sc.created_at, '%d/%m/%Y') as createdAt"),'users.firstName as createdBy')
                    ->get();
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
                'No.', 'Nama', 'Tipe','Pesan Online', 'Status', 'Dibuat Oleh', 'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Daftar Servis';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->fullName,
                $item->type == 1 ? 'Petshop' : ($item->type == 2 ? 'Grooming' : 'Klinik'),
                $item->optionPolicy1 == 1 ? 'Ya' : 'Tidak',
                $item->status == 1 ? 'Aktif' : 'Tidak Aktif',
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
