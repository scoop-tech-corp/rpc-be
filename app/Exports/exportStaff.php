<?php

namespace App\Exports;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class exportStaff implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {

        $users = DB::SELECT(
            '
                    select 
                        ROW_NUMBER() OVER(ORDER BY a.created_at DESC ) AS ID,
                        CONCAT(a.firstName ,\' \', a.middleName ,\' \', a.lastName ,\'(\', a.nickName ,\')\'  ) as name,
                        b.jobname as jobTitleid,
                        c.email as emailAddress,
                        CONCAT(d.phoneNumber) as phoneNumber,
                        CASE WHEN LOWER(d.type)=\'whatshapp \' then \'True \'  else \'False \' end as isWhatsapp,
                        CASE WHEN a.status=1 then \'Active\' else \'Non Active\' end as status,
                        e.locationName as location,
                        a.createdBy as createdBy,
                        DATE_FORMAT(a.created_at, "%d-%m-%Y") as createdAt
                        from users a
                        left join jobTitle b on b.id=a.jobTitleid
                        left join usersEmails c on c.usersId=a.id
                        left join usersTelephones d on d.usersId = a.id
                        left join location e on e.id = a.locationId
                    where 
                        a.isDeleted= ? and 
                        b.isActive= ? and 
                        d.usage = ? and
                        d.isDeleted = ? and
                        c.usage = ? and 
                        c.isDeleted = ? and
                        e.isDeleted = ?',
            [0, 1, 'Utama', 0, 'Utama', 0, 0],
        );

        return collect($users);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Job Title',
            'Email Address',
            'Phone Number',
            'Is Whatsapp',
            'Status',
            'Location',
            'Created By',
            'Created At',
        ];
    }

    public function title(): string
    {
        return 'Staff';
    }
}
