<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AddProductClinic implements ShouldAutoSize, WithHeadings, WithTitle, WithEvents
{
    public function headings(): array
    {
        return [
            [
                'Nama*', 'Nama Sederhana', 'SKU', 'Kode Merk', 'Kode Penyedia', 'Status', 'Tanggal Kedaluwarsa',
                'Pengeluaran', 'Harga Pasar', 'Harga Jual', 'Kode Lokasi', 'Stok', 'Stok Rendah', 'Batas Restock ulang',
                'Dapat Dikirim', 'Berat', 'Panjang', 'Lebar', 'Tinggi',
                'Dapat Membeli Produk', 'Dapat membeli secara online', 'Dapat membeli saat stok habis', 'Pengecekan stok selama ada penambahan atau pembuatan resep',
                'Tidak dikenakan Biaya', 'Persetujuan Office', 'Persetujuan Admin',
                'Perkenalan', 'Deskripsi', 'Kode Kategori Produk'
            ],
        ];
    }

    public function title(): string
    {
        return 'Isi Data Produk Klinik';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:AC1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('0070C0');
            },
        ];
    }
}
