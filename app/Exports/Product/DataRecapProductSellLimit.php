<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapProductSellLimit implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $search;
    protected $locationId;

    public function __construct($orderValue, $orderColumn, $search, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->search = $search;
        $this->locationId = $locationId;
    }

    public function collection()
    {

        $data = DB::table('products as ps')
            ->join('productLocations as psl', 'psl.productId', 'ps.id')
            ->join('location as loc', 'loc.Id', 'psl.locationId')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->join('users as u', 'ps.userId', 'u.id')
            ->select(
                'ps.fullName as fullName',
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("TRIM(ps.price)+0 as price"),
                DB::raw("TRIM(psl.inStock)+0 as inStock"),
                DB::raw("TRIM(psl.lowStock)+0 as lowStock"),
                DB::raw("TRIM(psl.reStockLimit)+0 as reStockLimit"),
                DB::raw("DATE_FORMAT(ps.expiredDate, '%d/%m/%Y') as expiredDate"),
                'loc.locationName as locationName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(ps.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('ps.isDeleted', '=', 0)
            ->where('psl.diffStock', '<=', 0)
            ->where('ps.category', '=', 'sell');

        $locations = $this->locationId;

        if (!$locations[0] == null) {

            $data = $data->whereIn('loc.id', $this->locationId);
        }

        if ($this->search) {
            $res = $this->Search($this->search);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $this->search . '%');

                for ($i = 1; $i < count($res); $i++) {

                    $data = $data->orWhere($res[$i], 'like', '%' . $this->search . '%');
                }
            } else {
                $data = [];
            }
        }


        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('ps.id', 'desc')->get();

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    private function Search($search)
    {
        $temp_column = null;

        $data = DB::table('products as ps')
            ->select(
                'ps.fullName as fullName'
            )
            ->where('ps.isDeleted', '=', 0);

        if ($search) {
            $data = $data->where('ps.fullName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'ps.fullName';
        }
        //------------------------

        $data = DB::table('products as ps')
            ->leftjoin('productSuppliers as psup', 'ps.productSupplierId', 'psup.id')
            ->select(
                DB::raw("IFNULL(psup.supplierName,'') as supplierName")
            )
            ->where('ps.isDeleted', '=', 0);

        if ($search) {
            $data = $data->where('psup.supplierName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'psup.supplierName';
        }
        //------------------------

        $data = DB::table('products as ps')
            ->leftjoin('productBrands as pb', 'ps.productBrandId', 'pb.Id')
            ->select(
                DB::raw("IFNULL(pb.brandName,'') as brandName")
            )
            ->where('ps.isDeleted', '=', 0);

        if ($search) {
            $data = $data->where('pb.brandName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pb.brandName';
        }
    }

    public function headings(): array
    {
        return [
            [
                'No.',
                'Nama Barang',
                'Merk',
                'Supplier',
                'Harga Jual',
                'Jumlah Barang',
                'Batas Stok Rendah',
                'Limit Restok',
                'Tanggal Kedaluwarsa',
                'Lokasi',
                'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Jual Limit';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->fullName,
                $item->brandName,
                $item->supplierName,
                $item->price,
                strval($item->inStock),
                strval($item->lowStock),
                strval($item->reStockLimit),
                $item->expiredDate,
                $item->locationName,
                $item->createdBy,
                $item->createdAt,
            ],
        ];
        return $res;
    }
}
