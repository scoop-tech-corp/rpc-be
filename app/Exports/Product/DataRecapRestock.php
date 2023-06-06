<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapRestock implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $locationId;
    protected $supplierId;
    protected $role;

    public function __construct($orderValue, $orderColumn, $locationId, $supplierId, $role)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->locationId = $locationId;
        $this->supplierId = $supplierId;
        $this->role = $role;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = DB::table('productRestocks as pr')
            ->join('productRestockDetails as prd', 'prd.productRestockId', 'pr.id')
            ->join('location as loc', 'loc.Id', 'pr.locationId')
            ->join('users as u', 'pr.userId', 'u.id')
            ->select(
                'pr.id',
                'pr.numberId',
                'loc.locationName',
                'pr.supplierName',
                'pr.variantProduct as products',
                'pr.totalProduct as quantity',
                DB::raw("
                CASE
                WHEN pr.status = 0 THEN 'Draft'
                WHEN pr.status = 1 THEN 'Waiting for Approval'
                WHEN pr.status = 2 THEN 'Reject'
                WHEN pr.status = 3 THEN 'Approved'
                WHEN pr.status = 4 THEN 'Waiting for Suppliers'
                WHEN pr.status = 5 THEN 'Product Received'
                END as status"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pr.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pr.isDeleted', '=', 0);

        $locations = $this->locationId;
        // if (!$locations[0] == null) {
        if ($locations) {

            $data = $data->whereIn('pr.locationId', $this->locationId);
        }

        $suppliers = $this->supplierId;
        // if (!$suppliers[0] == null) {
        if ($suppliers) {

            // $detail = DB::table('productRestockDetails as pr')
            //     ->select('pr.productRestockId')
            //     ->whereIn('pr.supplierId', $this->supplierId)
            //     ->where('pr.isDeleted', '=', 0)
            //     ->distinct()
            //     ->pluck('pr.productRestockId');

            $data = $data->whereIn('prd.supplierId', $suppliers);
        }

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('pr.updated_at', 'desc')->get();

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            [
                'No.', 'Nomor ID', 'Lokasi', 'Supplier',
                'Variasi Produk', 'Jumlah Produk',
                'Status', 'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Restok';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->numberId,
                $item->locationName,
                $item->supplierName,
                $item->products,
                $item->quantity,
                $item->status,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
