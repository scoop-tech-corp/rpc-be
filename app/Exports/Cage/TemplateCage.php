<?php

namespace App\Exports\Cage;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use DB;

class TemplateCage implements FromArray, ShouldAutoSize, WithHeadings, WithTitle, WithStyles
{
    use Exportable;

    public function array(): array
    {
        // Ambil nama lokasi yang aktif sebagai referensi
        $locations = DB::table('location')
            ->where('isDeleted', 0)
            ->orderBy('id')
            ->limit(3)
            ->pluck('locationName')
            ->toArray();

        // Baris contoh untuk setiap lokasi
        $examples = [];
        foreach ($locations as $locName) {
            $examples[] = [
                $locName,
                'Hotel-S-01',
                'hotel',
                'S',
                '1',
                'baik',
                '1',
                '1',
                'Catatan opsional',
            ];
        }

        // Fallback jika tidak ada lokasi
        if (empty($examples)) {
            $examples[] = [
                'Nama Lokasi (sesuai di sistem)',
                'Hotel-S-01',
                'hotel',
                'S',
                '1',
                'baik',
                '1',
                '1',
                'Catatan opsional',
            ];
        }

        return $examples;
    }

    public function headings(): array
    {
        return [[
            'lokasi',
            'nama_kandang',
            'tipe',
            'ukuran',
            'status',
            'kondisi',
            'kapasitas',
            'jumlah',
            'catatan',
        ]];
    }

    public function title(): string
    {
        return 'Template Import';
    }

    public function styles(Worksheet $sheet): array
    {
        // Styling header row
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Styling baris contoh (light blue)
        $lastRow = count($this->array()) + 1;
        if ($lastRow > 1) {
            $sheet->getStyle('A2:I' . $lastRow)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE3F2FD']],
            ]);
        }

        // Tambah komentar/panduan di bawah
        $guideRow = $lastRow + 2;
        $sheet->setCellValue('A' . $guideRow, 'PANDUAN PENGISIAN:');
        $sheet->getStyle('A' . $guideRow)->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $guides = [
            ['lokasi',       'Nama lokasi sesuai dengan yang terdaftar di sistem (wajib, case-sensitive)'],
            ['nama_kandang', 'Nama kandang (wajib, maks 100 karakter)'],
            ['tipe',         'Isi salah satu: hotel | breeding | salon | general (wajib)'],
            ['ukuran',       'Isi salah satu: S | M | L | XL (opsional, kosongkan jika tidak ada)'],
            ['status',       'Isi: 1 = Aktif, 0 = Nonaktif (wajib)'],
            ['kondisi',      'Isi salah satu: baik | perlu_perhatian | tidak_layak (wajib)'],
            ['kapasitas',    'Angka kapasitas hewan per kandang, minimal 1 (wajib)'],
            ['jumlah',       'Jumlah unit kandang, minimal 1 (wajib)'],
            ['catatan',      'Catatan tambahan (opsional, maks 300 karakter)'],
        ];

        foreach ($guides as $i => $guide) {
            $r = $guideRow + 1 + $i;
            $sheet->setCellValue('A' . $r, $guide[0]);
            $sheet->setCellValue('B' . $r, $guide[1]);
            $sheet->getStyle('A' . $r)->getFont()->setBold(true);
        }

        return [];
    }
}
