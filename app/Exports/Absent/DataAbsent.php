<?php

namespace App\Exports\Absent;

use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataAbsent implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{

    use Exportable;

    protected $sheets;

    protected $orderValue;
    protected $orderColumn;
    protected $dateFrom;
    protected $dateTo;
    protected $locationId;
    protected $staff;
    protected $statusPresent;

    public function __construct($orderValue, $orderColumn, $dateFrom, $dateTo, $locationId, $staff, $statusPresent)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->locationId = $locationId;
        $this->staff = $staff;
        $this->statusPresent = $statusPresent;
    }

    public function collection()
    {
        $data = DB::table('staffAbsents as sa')
            ->join('presentStatuses as ps', 'sa.statusPresent', 'ps.id')
            ->leftJoin('presentStatuses as ps1', 'sa.statusHome', 'ps1.id')
            ->join('users as u', 'sa.userId', 'u.id')
            ->join('usersLocation as ul', 'ul.usersId', 'u.id')
            ->join('location as l', 'ul.locationId', 'l.id')
            ->select(
                'sa.id',
                DB::raw("TRIM(CONCAT(CASE WHEN u.firstName = '' or u.firstName is null THEN '' ELSE CONCAT(u.firstName,' ') END
                ,CASE WHEN u.middleName = '' or u.middleName is null THEN '' ELSE CONCAT(u.middleName,' ') END,
                case when u.lastName = '' or u.lastName is null then '' else u.lastName end)) as name"),
                DB::raw("
                CONCAT(
                    CASE DAYOFWEEK(sa.presentTime)
                        WHEN 1 THEN 'Minggu'
                        WHEN 2 THEN 'Senin'
                        WHEN 3 THEN 'Selasa'
                        WHEN 4 THEN 'Rabu'
                        WHEN 5 THEN 'Kamis'
                        WHEN 6 THEN 'Jumat'
                        WHEN 7 THEN 'Sabtu'
                    END,
                    ', ',
                    DATE_FORMAT(sa.presentTime, '%e %b %Y')
                ) AS day
                "),
                DB::raw("TIME_FORMAT(sa.presentTime, '%H:%i') AS presentTime"),
                DB::raw("CASE WHEN sa.homeTime is null THEN '' ELSE TIME_FORMAT(sa.homeTime, '%H.%i') END AS homeTime"),
                DB::raw("CASE WHEN sa.duration is null THEN '' ELSE CONCAT(
                    HOUR(sa.duration), ' jam ',
                    MINUTE(sa.duration), ' menit'
                ) END AS duration"),
                'ps.statusName as presentStatus',
                DB::raw("CASE WHEN ps1.statusName is null THEN '' ELSE ps1.statusName END as homeStatus"),
                'sa.cityPresent as presentLocation',
                DB::raw("CASE WHEN sa.cityHome is null THEN '' ELSE sa.cityHome END as homeLocation"),
            )
            ->where('sa.isDeleted', '=', 0);

        if ($this->dateFrom && $this->dateTo) {

            $data = $data->whereBetween('sa.presentTime', [$this->dateFrom, $this->dateTo]);
        }

        $locations = $this->locationId;

        if (count($locations) > 0) {
            $data = $data->whereIn('l.id', $this->locationId);
        }

        $staffs = $this->staff;

        if (count($staffs) > 0) {
            $data = $data->whereIn('sa.userId', $this->staff);
        }

        $statusPresents = $this->statusPresent;

        if (count($statusPresents) > 0) {

            $data = $data->whereIn('sa.statusPresent', $this->statusPresent);
        }

        if ($this->orderValue) {
            $data = $data->orderBy($this->orderColumn, $this->orderValue);
        }

        $data = $data->groupBy(
            'sa.id',
            'u.firstName',
            'u.middleName',
            'u.lastName',
            'sa.presentTime',
            'sa.homeTime',
            'sa.duration',
            'ps.statusName',
            'ps1.statusName',
            'sa.cityPresent',
            'sa.cityHome'
        );

        $data = $data->orderBy('sa.updated_at', 'desc')->get();

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            [
                'No.', 'Nama', 'Hari', 'Jam Datang',
                'Jam Pulang', 'Durasi',
                'Status Kehadiran', 'Status Kepulangan', 'Lokasi Kehadiran',
                'Lokasi Kepulangan'
            ],
        ];
    }

    public function title(): string
    {
        return 'Rekap Absensi';
    }

    public function map($item): array
    {
        $res = [
            [
                $item->number,
                $item->name,
                $item->day,
                $item->presentTime,
                $item->homeTime,
                $item->duration,
                $item->presentStatus,
                $item->homeStatus,
                $item->presentLocation,
                $item->homeLocation,
            ],
        ];
        return $res;
    }
}
