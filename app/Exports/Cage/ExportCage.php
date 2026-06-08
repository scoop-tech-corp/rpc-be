<?php

namespace App\Exports\Cage;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ExportCage implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private ?string $search          = null,
        private ?string $locationId      = null,
        private ?string $type            = null,
        private ?string $conditionStatus = null,
        private ?string $status          = null
    ) {}

    public function sheets(): array
    {
        return [
            new DataCageAll(
                $this->search,
                $this->locationId,
                $this->type,
                $this->conditionStatus,
                $this->status
            ),
        ];
    }
}
