<?php

namespace App\Exports\Service;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapServiceTreatment implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
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

        $data = DB::table('treatments as tm')->where('tm.isDeleted', '=', 0);    
        $orderByColumn = $this->orderColumn == 'createdAt' ? 'tm.created_at' : $this->orderColumn;        
        $data = $data->join('users', 'tm.userId', '=', 'users.id')
                ->join('diagnose as d', 'tm.diagnose_id', '=', 'd.id')
                ->join('location as l', 'tm.location_id', '=', 'l.id')
                ->orderBy($orderByColumn, $this->orderValue)
                ->select('tm.id', 'tm.name as treatmentName', 'tm.column','d.name as diagnoseName', 'l.locationName','tm.status', 'tm.created_at', 'tm.updated_at', DB::raw("DATE_FORMAT(tm.created_at, '%d/%m/%Y') as createdAt"),'users.firstName as createdBy')
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
                'No.', 'Nama', 'Diagnosa','Lokasi', 'Durasi', 'Status','Dibuat Oleh', 'Tanggal Dibuat'
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
                $item->treatmentName,
                $item->diagnoseName,
                $item->locationName,
                $item->column ? $item->column : '0', 
                $item->status == 1 ? 'Aktif' : ($item->status == 2 ? 'Draft' : 'Tidak Aktif'),
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
