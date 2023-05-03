<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AddProductInventory implements ShouldAutoSize, WithHeadings, WithTitle, WithEvents
{
    public function headings(): array
    {
        return [
            [
                'Nama*', 'Kode Lokasi', 'Tipe Produk', 'Kode Produk', 'Kode Penggunaan', 'Tanggal Kondisi', 'Kondisi Barang',
                'Jumlah'
            ],
        ];
    }

    public function title(): string
    {
        return 'Isi Data Produk Inventori';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:H1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('0070C0');
            },
        ];
    }
}
