<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\productSupplierTypePhone;

class DataRecapSupplier implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;

    public function __construct($orderValue, $orderColumn)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
    }

    public function collection()
    {
        $idWa = productSupplierTypePhone::where('typeName', 'like', '%whatsapp%')->first();

        $data = DB::table('productSuppliers as ps')
            ->join('users as u', 'ps.userId', 'u.id')
            ->leftJoin('productSupplierAddresses as psa', 'ps.id', 'psa.productSupplierId')
            ->select(
                'ps.pic',
                'ps.supplierName',
                DB::raw("IFNULL(psa.streetAddress,'') as streetAddress"),

                DB::raw('CASE WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') > 0
                THEN (select number from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ' limit 1)
                WHEN (select count(*) from productSupplierPhones where productSupplierId=ps.id and typePhoneId=' . $idWa->id . ') = 0
                THEN (select number from productSupplierPhones where productSupplierId=ps.id limit 1) END as phoneNumber'),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->distinct()
            ->where('ps.isDeleted', '=', 0);

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('ps.updated_at', 'desc')->get();

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
                'No.', 'PIC', 'Nama Supplier', 'Alamat',
                'Nomor Ponsel', 'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Supplier';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->pic,
                $item->supplierName,
                $item->streetAddress,
                $item->phoneNumber,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
