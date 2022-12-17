<?php

namespace App\Exports;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class exportStaff implements FromCollection, WithHeadings, WithTitle
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users = DB::SELECT('

                    select 
                    ROW_NUMBER() OVER(ORDER BY a.created_at DESC ) AS ID
                    ,a.name
                    ,a.email    
                    ,b.roleName as role
                    from users a 
                    inner join users_role b on b.id=a.role
                    where a.isDeleted= ? and b.isActive=?' ,    
                    [0,1],
                );


        return collect($users);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Role',
        ];
    }

    public function title(): string
    {
        return 'Staff';
    }
}
