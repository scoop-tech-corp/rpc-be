<?php

namespace App\Exports\Promotion;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataPromo implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{

    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $type;

    public function __construct($orderValue, $orderColumn, $locationId, $type)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->type = $type;
    }

    public function collection()
    {
        $data = DB::table('promotionMasters as pm')
            ->join('promotionTypes as pt', 'pm.type', 'pt.id')
            ->join('promotionLocations as pl', 'pl.promoMasterId', 'pm.id')
            ->join('users as u', 'pm.userId', 'u.id')
            ->select(
                'pm.id as id',
                'pm.name',
                'pt.typeName as type',
                DB::raw("DATE_FORMAT(pm.startDate, '%d/%m/%Y') as startDate"),
                DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"),
                DB::raw("CASE WHEN pm.status = 1 then 'Active' ELSE 'Inactive' END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pm.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('pm.isDeleted', '=', 0);

        if ($this->locationId) {

            $data = $data->whereIn('pl.locationId', $this->locationId);
        }

        if ($this->type) {

            $data = $data->whereIn('pm.type', $this->type);
        }

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->groupBy(
            'pm.id',
            'pm.name',
            'pt.typeName',
            'pm.startDate',
            'pm.endDate',
            'pm.status',
            'pm.created_at',
            'u.firstName',
        );

        $data = $data->orderBy('pm.updated_at', 'desc')->get();

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
                'No.', 'Nama', 'Tipe Promo', 'Tanggal Mulai',
                'Tanggal Selesai', 'Status',
                'Dibuat Pada', 'Dibuat Oleh'
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap Promo';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->name,
                $item->type,
                $item->startDate,
                $item->endDate,
                $item->status,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
