<?php

namespace App\Exports\Finance;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataQuotation implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $status;
    protected $typeOfService;
    protected $dateFrom;
    protected $dateTo;
    protected $search;

    public function __construct(
        $orderValue,
        $orderColumn,
        $locationId,
        $status,
        $typeOfService,
        $dateFrom,
        $dateTo,
        $search
    ) {
        $this->orderValue    = $orderValue;
        $this->orderColumn   = $orderColumn;
        $this->locationId    = $locationId;
        $this->status        = $status;
        $this->typeOfService = $typeOfService;
        $this->dateFrom      = $dateFrom;
        $this->dateTo        = $dateTo;
        $this->search        = $search;
    }

    public function collection()
    {
        $data = DB::table('quotations as q')
            ->join('customer as c', 'q.customerId', 'c.id')
            ->join('location as l', 'q.locationId', 'l.id')
            ->join('users as u', 'q.userId', 'u.id')
            ->leftJoin('customerPets as cp', 'q.petId', 'cp.id')
            ->select(
                'q.quotationNo',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'cp.petName',
                'l.locationName',
                'q.typeOfService',
                DB::raw("TRIM(q.subtotalAmount)+0 as subtotalAmount"),
                DB::raw("TRIM(q.discountAmount)+0 as discountAmount"),
                DB::raw("TRIM(q.finalAmount)+0 as finalAmount"),
                'q.status',
                DB::raw("DATE_FORMAT(q.validUntil, '%d/%m/%Y') as validUntil"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(q.created_at, '%d/%m/%Y %H:%i') as createdAt")
            )
            ->where('q.isDeleted', 0);

        if ($this->locationId) {
            $data = $data->where('q.locationId', $this->locationId);
        }

        if ($this->status) {
            $data = $data->where('q.status', $this->status);
        }

        if ($this->typeOfService) {
            $data = $data->where('q.typeOfService', $this->typeOfService);
        }

        if ($this->dateFrom && $this->dateTo) {
            $data = $data->whereBetween(DB::raw("DATE(q.created_at)"), [$this->dateFrom, $this->dateTo]);
        }

        if ($this->search) {
            $keyword = $this->search;
            $data = $data->where(function ($q) use ($keyword) {
                $q->where('quotations.quotationNo', 'like', "%$keyword%")
                  ->orWhere(DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, ''))"), 'like', "%$keyword%")
                  ->orWhere('cp.petName', 'like', "%$keyword%");
            });
        }

        $allowedColumns = [
            'quotationNo'  => 'q.quotationNo',
            'customerName' => DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, ''))"),
            'locationName' => 'l.locationName',
            'finalAmount'  => 'q.finalAmount',
            'validUntil'   => 'q.validUntil',
            'createdAt'    => 'q.created_at',
        ];

        $orderCol = $allowedColumns[$this->orderColumn] ?? 'q.created_at';
        $orderDir = in_array(strtolower($this->orderValue ?? ''), ['asc', 'desc']) ? $this->orderValue : 'desc';
        $data = $data->orderBy($orderCol, $orderDir)->get();

        $no = 1;
        foreach ($data as $row) {
            $row->number = $no++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [[
            'No.',
            'No. Quotation',
            'Customer',
            'No. Member',
            'Hewan',
            'Cabang',
            'Jenis Layanan',
            'Subtotal (Rp)',
            'Diskon (Rp)',
            'Total (Rp)',
            'Status',
            'Berlaku Hingga',
            'Dibuat Oleh',
            'Tanggal Dibuat',
        ]];
    }

    public function title(): string
    {
        return 'Daftar Quotation';
    }

    public function map($item): array
    {
        $serviceLabel = match($item->typeOfService) {
            'clinic'   => 'Pet Clinic',
            'hotel'    => 'Pet Hotel',
            'salon'    => 'Salon',
            'grooming' => 'Grooming',
            'shop'     => 'Pet Shop',
            default    => ucfirst($item->typeOfService),
        };

        return [[
            $item->number,
            $item->quotationNo,
            $item->customerName,
            $item->memberNo ?? '-',
            $item->petName  ?? '-',
            $item->locationName,
            $serviceLabel,
            $item->subtotalAmount,
            $item->discountAmount,
            $item->finalAmount,
            ucfirst($item->status),
            $item->validUntil,
            $item->createdBy,
            $item->createdAt,
        ]];
    }
}
