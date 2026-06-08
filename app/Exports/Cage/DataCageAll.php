<?php

namespace App\Exports\Cage;

use DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DataCageAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping, WithStyles
{
    use Exportable;

    private ?string $search;
    private ?string $locationId;
    private ?string $type;
    private ?string $conditionStatus;
    private ?string $status;

    public function __construct(
        ?string $search         = null,
        ?string $locationId     = null,
        ?string $type           = null,
        ?string $conditionStatus = null,
        ?string $status         = null
    ) {
        $this->search          = $search;
        $this->locationId      = $locationId;
        $this->type            = $type;
        $this->conditionStatus = $conditionStatus;
        $this->status          = $status;
    }

    public function collection()
    {
        $query = DB::table('cages as c')
            ->join('location as l', 'l.id', '=', 'c.locationId')
            ->where('c.isDeleted', 0)
            ->where('l.isDeleted', 0);

        if ($this->locationId) {
            $ids = array_filter(explode(',', $this->locationId));
            if ($ids) $query->whereIn('c.locationId', $ids);
        }
        if ($this->type) {
            $types = array_filter(explode(',', $this->type));
            if ($types) $query->whereIn('c.type', $types);
        }
        if ($this->conditionStatus) {
            $conds = array_filter(explode(',', $this->conditionStatus));
            if ($conds) $query->whereIn('c.conditionStatus', $conds);
        }
        if ($this->status !== null && $this->status !== '') {
            $query->where('c.status', $this->status);
        }
        if ($this->search) {
            $query->where('c.cageName', 'like', '%' . $this->search . '%');
        }

        $data = $query->select(
            'c.id',
            'l.locationName',
            'c.cageName',
            'c.type',
            'c.size',
            'c.status',
            'c.conditionStatus',
            'c.capacity',
            'c.amount',
            'c.notes'
        )->orderBy('l.locationName')->orderBy('c.cageName')->get();

        // Beri nomor urut
        $no = 1;
        foreach ($data as $row) {
            $row->no = $no++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [['No.', 'Lokasi', 'Nama Kandang', 'Tipe', 'Ukuran', 'Status', 'Kondisi', 'Kapasitas', 'Jumlah Unit', 'Catatan']];
    }

    public function map($row): array
    {
        $typeMap      = ['hotel' => 'Hotel', 'breeding' => 'Breeding', 'salon' => 'Salon', 'general' => 'General'];
        $condMap      = ['baik' => 'Baik', 'perlu_perhatian' => 'Perlu Perhatian', 'tidak_layak' => 'Tidak Layak'];
        $statusMap    = [1 => 'Aktif', 0 => 'Nonaktif'];

        return [[
            $row->no,
            $row->locationName,
            $row->cageName,
            $typeMap[$row->type]          ?? $row->type,
            $row->size                    ?? '-',
            $statusMap[(int)$row->status] ?? '-',
            $condMap[$row->conditionStatus] ?? $row->conditionStatus,
            $row->capacity,
            $row->amount,
            $row->notes                   ?? '-',
        ]];
    }

    public function title(): string
    {
        return 'Data Kandang';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E7D32']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
