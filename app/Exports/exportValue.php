<?php

namespace App\Exports;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class exportValue implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $data = DB::SELECT('select
                            ROW_NUMBER() OVER(ORDER BY a.created_at DESC ) AS ID,
                            a.locationName,
                            b.addressName,
                            d.namaKabupaten,
                            CONCAT(c.phoneNumber ,\' \', c.usage) as phoneNumber,
                            CASE WHEN a.status=1 then \'Active\' else \'Non Active\' end as status
                            from location a
                            left join location_detail_address b on b.codeLocation=a.codeLocation
                            left join location_telephone c on c.codeLocation=a.codeLocation
                            left join kabupaten d on d.kodeKabupaten=b.cityCode
                            where b.isPrimary= ? and c.usage=? and a.isDeleted=?',
                            [1, 'utama', 0],
        );

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'No',
            'Location Name',
            'Address',
            'CityName',
            'Phone Number',
            'Status',
        ];
    }

    public function title(): string
    {
        return 'Location';
    }

}
