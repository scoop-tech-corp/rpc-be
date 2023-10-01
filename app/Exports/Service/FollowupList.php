<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use DB;
use Carbon\Carbon;

class FollowupList implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    public function collection()
    {
        $data = DB::table('services')
            ->join('users', 'users.id', '=', 'services.userId')
            ->select('services.id', 'services.fullName', 'services.type', 'services.optionPolicy1', 'services.created_at', 'services.status', 'users.firstName')
            ->where('services.isDeleted', '=', 0)
            ->get();

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Kode Service', 'Nama Service', 'Tipe', 'Pesan Online', 'Status', 'Dibuat Oleh', 'Dibuat Pada'],
        ];
    }

    public function title(): string
    {
        return 'Data Followup';
    }

    public function map($listOfService): array
    {
        return [
            $listOfService->id,
            $listOfService->fullName,
            $typeName = $listOfService->type == 1 ? 'Petshop' : ($listOfService->type == 2 ? 'Grooming' : 'Klinik'),
            $listOfService->optionPolicy1 == 1 ? 'Ya' : 'Tidak',
            $listOfService->status == 1 ? 'Aktif' : 'Tidak Aktif',
            $listOfService->firstName,
            Carbon::parse($listOfService->created_at)->format('d/m/Y'),
        ];
    }
}
