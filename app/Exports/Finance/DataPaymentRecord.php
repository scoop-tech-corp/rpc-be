<?php

namespace App\Exports\Finance;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Http\Controllers\Finance\FinancePaymentRecordController;

class DataPaymentRecord implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Request $request;
    protected int $rowNum = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $ctrl = new FinancePaymentRecordController();
        return $ctrl->allData($this->request);
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Nota Bayar',
            'No. Invoice Asal',
            'Customer',
            'No. Member',
            'Cabang',
            'Jenis Layanan',
            'Metode Bayar',
            'Jumlah Bayar',
            'Status Konfirmasi',
            'Jatuh Tempo',
            'Dicatat Oleh',
            'Tgl Dicatat',
        ];
    }

    public function map($row): array
    {
        $this->rowNum++;
        return [
            $this->rowNum,
            $row->notaNumber   ?? '-',
            $row->invoiceNumber ?? '-',
            $row->customerName ?? '-',
            $row->memberNo     ?? '-',
            $row->locationName ?? '-',
            $row->serviceType  ?? '-',
            $row->paymentMethod ?? '-',
            (float) $row->amountPaid,
            $row->isPayed ? 'Confirmed' : 'Pending',
            $row->nextPayment  ?? '-',
            $row->createdBy    ?? '-',
            $row->createdAt    ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
