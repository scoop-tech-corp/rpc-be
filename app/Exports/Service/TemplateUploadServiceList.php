<?php

namespace App\Exports\Service;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Service\AddServiceList;
use App\Exports\Service\ExampleAddServiceList;
use App\Exports\Service\LocationList;
use App\Exports\Service\CategoryList;
use App\Exports\Service\FollowupList;
use App\Exports\Service\CustomerGroupList;

class TemplateUploadServiceList implements WithMultipleSheets
{
    use Exportable;

    protected $sheets;

    function array(): array
    {
        return $this->sheets;
    }

    public function sheets(): array
    {
        $sheets = [];

        $sheets = [
            new AddServiceList(),
            new ExampleAddServiceList(),
            new LocationList(),
            new CategoryList(),
            new FollowupList(),
        ];

        return $sheets;
    }
}
