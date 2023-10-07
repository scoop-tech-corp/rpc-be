<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AddServiceList implements ShouldAutoSize, WithHeadings, WithTitle, WithEvents
{
    public function headings(): array
    {
        return [
            [
                'Tipe*', 
                'Nama*', 
                'Nama Singkat',
                'Status*',
                'Lokasi*',
                'Perkenalan',
                'Deskripsi',
                'Ketentuan',
                'Dapat dipesan Online',
                'Rekam medis alasan kunjungan',
                'Rekam Diagnosa',
                'Followup',
                'Kategori',
            ],
        ];
    }

    public function title(): string
    {
        return 'Isi Data Layanan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:D1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('70AD47');

                $event->sheet->getDelegate()->getStyle('E1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFC000');
                
                $event->sheet->getDelegate()->getStyle('F1:G1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('4472C4');

                $event->sheet->getDelegate()->getStyle('H1:K1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('FFFF00');

                $event->sheet->getDelegate()->getStyle('L1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('BF9000');

                $event->sheet->getDelegate()->getStyle('M1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('7F7F7F');
            },
        ];
    }
}
