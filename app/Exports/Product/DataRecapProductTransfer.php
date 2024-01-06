<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapProductTransfer implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationDestinationId;
    protected $status;

    public function __construct($orderValue, $orderColumn, $locationDestinationId, $status)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationDestinationId = $locationDestinationId;
        $this->status = $status;
    }

    public function collection()
    {
        $data = DB::table('productTransfers as pt')
            ->join('users as u', 'pt.userId', 'u.id')
            ->leftjoin('location as lo', 'pt.locationIdOrigin', 'lo.id')
            ->leftjoin('location as ld', 'pt.locationIdDestination', 'ld.id')
            ->select(
                'pt.numberId',
                'pt.transferNumber',
                'pt.transferName',
                'pt.variantProduct',
                'pt.totalProduct',
                'pt.totalProduct',
                DB::raw("
                CASE
                WHEN pt.status = 0 THEN 'Draft'
                WHEN pt.status = 1 THEN 'Waiting for Approval'
                WHEN pt.status = 2 THEN 'Rejected'
                WHEN pt.status = 3 THEN 'Approved'
                WHEN pt.status = 4 THEN 'Product Sent'
                WHEN pt.status = 5 THEN 'Product Received'
                END as status"),
                'lo.locationName as locationOriginName',
                'ld.locationName as locationDestinationName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pt.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pt.isDeleted', '=', 0);

        $locations = $this->locationDestinationId;

        // if ($this->locationDestinationId) {
        if (!$locations[0] == null) {
            $data = $data->whereIn('ld.id', $this->locationDestinationId);
        }

        if ($this->status) {
            $data = $data->where('pt.status', '=', $this->status);
        }

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('pt.updated_at', 'desc')->get();

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
                'No.', 'Nomor ID', 'Nomor Transfer', 'Nama Transfer', 'Varian Produk', 'Total Produk',
                'Status', 'Cabang Asal', 'Cabang Tujuan',
                'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Transfer';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->numberId,
                $item->transferNumber,
                $item->transferName,
                strval($item->variantProduct),
                strval($item->totalProduct),
                $item->status,
                $item->locationOriginName,
                $item->locationDestinationName,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
