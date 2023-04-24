<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;


class ExampleAddProductSell implements ShouldAutoSize, WithHeadings, WithTitle, WithEvents, FromCollection
{
    public function collection()
    {

        $data[] = array(
            'productName' => 'Whiskas 5kg',
            'simpleName' => 'whiskas',
            'skuNumber' => '1234',
            'brandCode' => '1',
            'supplierCode' => '4',
            'status' => '1',
            'expiredDate' => '2023/04/28',
            'cost' => '15000',
            'marketplace' => '19000',
            'sellPrice' => '20000',
            'codeLocation' => '1;4;5;6',
            'stock' => '10;20;20;20',
            'lowStock' => '5;8;10;10',
            'reStockLimit' => '5;10;15;15',
            'isDeliver' => '1',
            'weight' => '2',
            'length' => '5',
            'width' => '6',
            'height' => '8',
            'isCanBuy' => '1',
            'isCanBuyOnline' => '1',
            'isCanBuyWhenEmptyStock' => '0',
            'isCanBuyWhenEmptyStock' => '1',
            'isCheckStock' => '1',
            'isNonChargeable' => '1',
            'isOfficeApproval' => '1',
            'isAdminApproval' => '1',
            'introduction' => 'ini akan menjadi sebuah barang bagus',
            'description' => 'Barang dengan spesifikasi yang cukup menarik',
            'categoryCode' => '2;5;6',
        );

        return collect($data);
    }

    public function headings(): array
    {
        return [
            [
                'Nama*', 'Nama Sederhana', 'SKU', 'Kode Merk', 'Kode Penyedia', 'Status', 'Tanggal Kedaluwarsa',
                'Pengeluaran', 'Harga Pasar', 'Harga Jual', 'Kode Lokasi', 'Stok', 'Stok Rendah',
                'Batas Restock ulang',
                'Dapat Dikirim', 'Berat', 'Panjang', 'Lebar', 'Tinggi',
                'Dapat Membeli Produk', 'Dapat membeli secara online', 'Dapat membeli saat stok habis',
                'Pengecekan stok selama ada penambahan atau pembuatan resep',
                'Tidak dikenakan Biaya', 'Persetujuan Office', 'Persetujuan Admin',
                'Perkenalan', 'Deskripsi', 'Kode Kategori Produk'
            ],
        ];
    }

    public function title(): string
    {
        return 'Contoh Pengisian Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getStyle('A1:AC1')
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('8DB4E2');
            },
        ];
    }

    public function map($listOfExample): array
    {
        return [
            $listOfExample->productName,
            $listOfExample->simpleName,
            $listOfExample->skuNumber,
            $listOfExample->brandCode,
            $listOfExample->supplierCode,
            $listOfExample->status,
            $listOfExample->expiredDate,
            $listOfExample->cost,
            $listOfExample->marketplace,
            $listOfExample->sellPrice,
            $listOfExample->codeLocation,
            $listOfExample->stock,
            $listOfExample->lowStock,
            $listOfExample->reStockLimit,
            $listOfExample->isDeliver,
            $listOfExample->weight,
            $listOfExample->length,
            $listOfExample->width,
            $listOfExample->height,
            $listOfExample->isCanBuy,
            $listOfExample->isCanBuyOnline,
            $listOfExample->isCanBuyWhenEmptyStock,
            $listOfExample->isCanBuyWhenEmptyStock,
            $listOfExample->isCheckStock,
            $listOfExample->isNonChargeable,
            $listOfExample->isOfficeApproval,
            $listOfExample->isAdminApproval,
            $listOfExample->introduction,
            $listOfExample->description,
            $listOfExample->categoryCode,
        ];
    }
}
