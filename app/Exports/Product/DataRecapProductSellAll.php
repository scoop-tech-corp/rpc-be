<?php

namespace App\Exports\Product;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataRecapProductSellAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
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
        $data = DB::table('productSells as pc')
            ->join('productSellLocations as pcl', 'pcl.productSellId', 'pc.id')
            ->join('location as loc', 'loc.Id', 'pcl.locationId')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->join('users as u', 'pc.userId', 'u.id')
            ->select(
                'pc.fullName as fullName',
                DB::raw("IFNULL(pb.brandName,'') as brandName"),
                DB::raw("IFNULL(psup.supplierName,'') as supplierName"),
                DB::raw("TRIM(pc.price)+0 as price"),
                DB::raw("TRIM(pcl.inStock)+0 as inStock"),
                DB::raw("TRIM(pcl.lowStock)+0 as lowStock"),
                DB::raw("TRIM(pcl.reStockLimit)+0 as reStockLimit"),
                DB::raw("TRIM(pc.expiredDate)+0 as expiredDate"),
                DB::raw("DATE_FORMAT(pc.created_at, '%d/%m/%Y') as createdAt"),
                'loc.locationName as locationName',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(pc.created_at, '%d/%m/%Y') as createdAt")
            )
            ->where('pc.isDeleted', '=', 0);

        if ($this->locationId) {

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
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }


        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->orderBy('pc.id', 'desc')->get();

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

        $data = DB::table('productSells as pc')
            ->select(
                'pc.fullName as fullName'
            )
            ->where('pc.isDeleted', '=', 0);

        if ($search) {
            $data = $data->where('pc.fullName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'pc.fullName';
        }
        //------------------------

        $data = DB::table('productSells as pc')
            ->leftjoin('productSuppliers as psup', 'pc.productSupplierId', 'psup.id')
            ->select(
                DB::raw("IFNULL(psup.supplierName,'') as supplierName")
            )
            ->where('pc.isDeleted', '=', 0);

        if ($search) {
            $data = $data->where('psup.supplierName', 'like', '%' . $search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'psup.supplierName';
        }
        //------------------------

        $data = DB::table('productSells as pc')
            ->leftjoin('productBrands as pb', 'pc.productBrandId', 'pb.Id')
            ->select(
                DB::raw("IFNULL(pb.brandName,'') as brandName")
            )
            ->where('pc.isDeleted', '=', 0);

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
                'No.', 'Nama Barang', 'Merk', 'Supplier',
                'Harga Jual', 'Jumlah Barang',
                'Batas Stok Rendah', 'Limit Restok', 'Tanggal Kedaluwarsa',
                'Lokasi', 'Dibuat Oleh',
                'Tanggal Dibuat'
            ],
        ];
    }

    public function title(): string
    {
        return 'Produk Jual';
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
