<?php

namespace App\Exports\Finance;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Http\Controllers\Finance\FinanceRefundController;

class DataRefund implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Request $request;
    protected int $rowNum = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $ctrl = new FinanceRefundController();
        return $ctrl->allData($this->request);
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Refund',
            'No. Invoice Asal',
            'Customer',
            'No. Member',
            'Cabang',
            'Jenis Layanan',
            'Metode Refund',
            'Jumlah Refund',
            'Alasan',
            'Catatan',
            'Status',
            'Dicatat Oleh',
            'Tgl Refund',
        ];
    }

    public function map($row): array
    {
        $this->rowNum++;
        return [
            $this->rowNum,
            $row->refundNumber   ?? '-',
            $row->invoiceNumber  ?? '-',
            $row->customerName   ?? '-',
            $row->memberNo       ?? '-',
            $row->locationName   ?? '-',
            $row->serviceType    ?? '-',
            $row->paymentMethod  ?? '-',
            (float) $row->amount,
            $row->reason         ?? '-',
            $row->notes          ?? '-',
            $row->status ? 'Approved' : 'Pending',
            $row->createdBy      ?? '-',
            $row->createdAt      ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
