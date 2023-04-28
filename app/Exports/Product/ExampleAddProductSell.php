<?php

namespace App\Exports\Product;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;


class ExampleAddProductSell implements FromView, WithTitle
{
    public function view(): View
    {
        return view('example-input-product-sell');
    }

    public function title(): string
    {
        return 'Contoh Pengisian Template';
    }
}
