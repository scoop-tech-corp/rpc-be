<?php

namespace App\Exports\Finance;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PaymentRecordReport implements WithMultipleSheets
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        return [
            'Payment Record' => new DataPaymentRecord($this->request),
        ];
    }
}
