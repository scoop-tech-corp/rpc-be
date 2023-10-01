<?php

namespace App\Exports\Service;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;


class ExampleAddServiceList implements FromView, WithTitle
{
    public function view(): View
    {
        return view('example-input-service-list');
    }

    public function title(): string
    {
        return 'Contoh Pengisian Template';
    }
}
