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
                        ROW_NUMBER() OVER(ORDER BY users.created_at DESC ) AS ID,
                        CONCAT(users.firstName ,\' \', users.middleName ,\' \', users.lastName ,\'(\', users.nickName ,\')\'  ) as name,
                        jobTitle.jobname as jobTitleid,
                        usersEmails.email as emailAddress,
                        CONCAT(userstelephones.phoneNumber) as phoneNumber,
                        CASE WHEN LOWER(userstelephones.type)=\'whatshapp \' then \'True \'  else \'False \' end as isWhatsapp,
                        CASE WHEN users.status=1 then \'Active\' else \'Non Active\' end as status,
                        location.locationName as location,
                        users.createdBy as createdBy,
                        DATE_FORMAT(users.created_at, "%d-%m-%Y") as createdAt
                        from users users
                        left join jobTitle jobTitle on jobTitle.id=users.jobTitleid
                        left join usersEmails usersEmails on usersEmails.usersId=users.id
                        left join userstelephones userstelephones on userstelephones.usersId = users.id
                        left join location location on location.id = users.locationId
                    where 
                        users.isDeleted= ? and 
                        jobTitle.isActive= ? and 
                        userstelephones.usage = ? and
                        userstelephones.isDeleted = ? and
                        usersEmails.usage = ? and 
                        usersEmails.isDeleted = ? and
                        location.isDeleted = ?',
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
