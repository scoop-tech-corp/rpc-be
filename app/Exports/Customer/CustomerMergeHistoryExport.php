<?php

namespace App\Exports\Customer;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class CustomerMergeHistoryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $dateFrom;
    protected $dateTo;
    protected $locationId;

    public function __construct($dateFrom = null, $dateTo = null, $locationId = null)
    {
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->locationId = $locationId;
    }

    public function collection()
    {
        $query = DB::table('customer_merge_logs as ml')
            ->leftJoin('users as u', 'u.id', '=', 'ml.userId')
            ->leftJoin('customer as c', 'c.id', '=', 'ml.targetCustomerId')
            ->leftJoin('location as l', 'l.id', '=', 'c.locationId')
            ->select(
                'ml.id',
                'ml.sourceCustomerName',
                'ml.targetCustomerName',
                'ml.transferredRelations',
                'ml.created_at',
                DB::raw("CONCAT_WS(' ', u.firstName, u.middleName, u.lastName) as performedBy"),
                'l.locationName'
            )
            ->orderBy('ml.created_at', 'desc');

        if ($this->dateFrom) {
            $query->whereDate('ml.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('ml.created_at', '<=', $this->dateTo);
        }
        if ($this->locationId) {
            $query->where('c.locationId', $this->locationId);
        }

        $data = $query->get();

        $no = 1;
        foreach ($data as $row) {
            $row->no = $no++;
            $relations = json_decode($row->transferredRelations, true) ?? [];
            $summary   = [];
            $labelMap  = [
                'pets'                 => 'Hewan',
                'telephones'           => 'No. Telp',
                'emails'               => 'Email',
                'addresses'            => 'Alamat',
                'transactionPetClinics'=> 'Klinik',
                'transactionPetHotels' => 'Hotel',
                'transactionPetShop'   => 'Petshop',
                'transactionPetSalons' => 'Salon',
                'transactionBreedings' => 'Breeding',
                'transactions'         => 'Transaksi',
                'bookings'             => 'Booking',
                'deliveryOrders'       => 'Delivery',
                'queues'               => 'Antrian',
                'reminders'            => 'Reminder',
            ];
            foreach ($relations as $key => $count) {
                $label   = $labelMap[$key] ?? $key;
                $summary[] = "{$label}: {$count}";
            }
            $row->relationSummary = implode(', ', $summary) ?: '-';
        }

        return $data;
    }

    public function headings(): array
    {
        return [[
            'No.',
            'Customer Sumber',
            'Customer Target',
            'Lokasi',
            'Relasi Dipindah',
            'Dilakukan Oleh',
            'Tanggal Merge',
        ]];
    }

    public function title(): string
    {
        return 'Riwayat Merge Customer';
    }

    public function map($row): array
    {
        return [[
            $row->no,
            $row->sourceCustomerName,
            $row->targetCustomerName,
            $row->locationName ?? '-',
            $row->relationSummary,
            $row->performedBy,
            $row->created_at,
        ]];
    }
}
