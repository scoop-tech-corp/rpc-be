<?php

namespace App\Imports\Staff;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AddDataStaff implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function model(array $row)
    {
    }

    public function rules(): array
    {
        return [
            '*.nama_barang' => 'required|string',
            '*.jumlah_barang' => 'required|integer',
            '*.limit_barang' => 'required|integer',
            '*.tanggal_kedaluwarsa_barang_ddmmyyyy' => 'required|date_format:d/m/Y',
            '*.kode_satuan_barang' => 'required|integer',
            '*.kode_kategori_barang' => 'required|integer',
            '*.kode_cabang_barang' => 'required|integer',
        ];
    }
}
