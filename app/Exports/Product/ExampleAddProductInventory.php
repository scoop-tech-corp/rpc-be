<?php

namespace App\Exports\Product;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExampleAddProductInventory implements FromView, WithTitle
{
    public function view(): View
    {
        return view('example-input-product-inventory');
    }

    public function title(): string
    {
        return 'Contoh Pengisian Template';
    }
}
