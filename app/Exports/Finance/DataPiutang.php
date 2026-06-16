<?php

namespace App\Exports\Finance;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Http\Controllers\Finance\FinancePiutangController;

class DataPiutang implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected Request $request;
    protected int $rowNum = 0;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $ctrl = new FinancePiutangController();
        return $ctrl->allData($this->request);
    }

    public function headings(): array
    {
        return [
            'No',
            'No. Invoice',
            'Customer',
            'No. Member',
            'Cabang',
            'Jenis Layanan',
            'Tgl Transaksi',
            'Jatuh Tempo',
            'Total Tagihan',
            'Terbayar',
            'Sisa Tagihan',
            'Aging',
            'Hari Lewat',
        ];
    }

    public function map($row): array
    {
        $this->rowNum++;
        $aging = match ($row->agingBucket ?? 'current') {
            'current' => 'Belum Jatuh Tempo',
            '1-30'    => '1 - 30 Hari',
            '31-60'   => '31 - 60 Hari',
            '61-90'   => '61 - 90 Hari',
            '>90'     => '> 90 Hari',
            default   => '-',
        };

        return [
            $this->rowNum,
            $row->invoiceNumber  ?? '-',
            $row->customerName   ?? '-',
            $row->memberNo       ?? '-',
            $row->locationName   ?? '-',
            $row->serviceType    ?? '-',
            $row->transactionDate ?? '-',
            $row->dueDate        ?? '-',
            (float) $row->total,
            (float) $row->paidAmount,
            (float) $row->remaining,
            $aging,
            (int)   $row->daysOverdue,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
