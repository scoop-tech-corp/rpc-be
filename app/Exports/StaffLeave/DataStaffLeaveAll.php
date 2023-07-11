<?php

namespace App\Exports\StaffLeave;

use DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\Exportable;

class DataStaffLeaveAll implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle, WithMapping
{
    use Exportable;

    protected $sheets;
    protected $orderValue;
    protected $orderColumn;
    protected $status;
    protected $locationId;
    protected $userId;
    protected $rolesIndex;
    protected $fromDate;
    protected $toDate;


    public function __construct($orderValue, $orderColumn, $status, $rolesIndex, $fromDate, $toDate, $userId, $locationId)
    {
        $this->orderValue = $orderValue;
        $this->orderColumn = $orderColumn;
        $this->status = $status;
        $this->rolesIndex = $rolesIndex;
        $this->fromDate =   $fromDate;
        $this->toDate = $toDate;
        $this->userId = $userId;
        $this->locationId = $locationId;
    }

    public function collection()
    {

        $defaultRowPerPage = 5;
        $defaultOrderBy = "asc";

        $data = null;

        if ($this->rolesIndex  == 1) {


            if (strtolower($this->status) == "pending") {

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId',  'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status],]);
            } elseif (strtolower($this->status) == "approve") {

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId',  'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.approveOrRejectedBy as approvedBy', 'a.approveOrRejectedDate as approvedAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status],]);
            } else {

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId', 'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.approveOrRejectedBy as rejectedBy', 'a.rejectedReason as  rejectedReason', 'a.approveOrRejectedDate as rejectedAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status],]);
            }


            info($data->get());

            if (strtotime($this->fromDate) !== false && strtotime($this->toDate) !== false) {

                $start = Carbon::parse($this->fromDate);
                $end = Carbon::parse($this->toDate);

                if ($end < $start) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['To date must higher than from date!!'],
                    ], 422);
                }

                $data = $data->whereBetween('fromDate', [$this->fromDate, $this->toDate]);
            }


            info($this->locationId);
            if (!is_null($this->locationId)) {
                info("masuk sini");
                $test = $this->locationId;

                $data = $data->where(function ($query) use ($test) {
                    foreach ($test as $id) {
                        $query->orWhereRaw("FIND_IN_SET(?, a.locationId)", [$id]);
                    }
                });
            }


            info($data->get());
        } else {

            if (strtolower($this->status) == "pending") {

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId', 'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status], ['a.usersId', '=', $this->userId],]);
            } elseif (strtolower($this->status) == "approve") {


                $data = DB::table('leaveRequest as a')
                    ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId', 'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.approveOrRejectedBy as approvedBy', 'a.approveOrRejectedDate as approvedAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status], ['a.usersId', '=', $this->userId],]);
            } else {

                $data = DB::table('leaveRequest as a')
                    ->leftjoin('location as c', 'a.locationId', '=', 'c.id')
                    ->leftjoin('jobTitle as b', 'a.jobTitle', '=', 'b.id')
                    ->select('a.id as leaveRequestId', 'a.requesterName as requester', 'a.locationId as locationId',  'a.locationName as locationName', 'b.jobName as jobName', 'a.leaveType as leaveType', 'a.fromDate as date', 'a.duration as days', 'a.remark as remark', 'a.created_at as createdAt', 'a.approveOrRejectedBy as rejectedBy', 'a.rejectedReason as  rejectedReason', 'a.approveOrRejectedDate as rejectedAt', 'a.updated_at as updatedAt')
                    ->where([['a.status', '=', $this->status], ['a.usersId', '=', $this->userId],]);
            }


            if (strtotime($this->fromDate) !== false && strtotime($this->toDate) !== false) {

                $start = Carbon::parse($this->fromDate);
                $end = Carbon::parse($this->toDate);

                if ($end < $start) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => ['To date must higher than from date!!'],
                    ], 422);
                }

                $data = $data->whereBetween('fromDate', [$this->fromDate, $this->toDate]);
            }
        }

        if ($this->orderValue) {
            $defaultOrderBy = $this->orderValue;
        }

        $checkOrder = null;
        $listOrder = [];

        if ($this->orderColumn && $defaultOrderBy) {

            if (strtolower($this->status) == "pending") {
                $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt');
            } elseif (strtolower($this->status) == "approve") {
                $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'approvedBy', 'approvedAt');
            } else {
                $listOrder = array('requesterName', 'jobName', 'locationName', 'leaveType', 'fromDate', 'duration', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt');
            }

            if (!in_array($this->orderColumn, $listOrder)) {

                return response()->json([
                    'result' => 'failed',
                    'message' => 'Please try different Order Column',
                    'orderColumn' => $listOrder,
                ]);
            }

            if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                return response()->json([
                    'result' => 'failed',
                    'message' => 'order value must Ascending: ASC or Descending: DESC ',
                ]);
            }

            $checkOrder = true;
        }


        if ($checkOrder) {

            if (strtolower($this->status) == "pending") {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt')->orderBy($this->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc')->get();
            } elseif (strtolower($this->status) == "approve") {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt', 'approvedBy', 'approvedAt')->orderBy($this->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc')->get();
            } else {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt')->orderBy($this->orderColumn, $defaultOrderBy)->orderBy('updatedAt', 'desc')->get();
            }
        } else {

            if (strtolower($this->status) == "pending") {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt')->orderBy('updatedAt', 'desc')->get();
            } elseif (strtolower($this->status) == "approve") {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt', 'approvedBy', 'approvedAt')->orderBy('updatedAt', 'desc')->get();
            } else {
                $data = DB::table($data)->select('requester', 'jobName', 'locationName', 'leaveType', 'date', 'days', 'remark', 'createdAt', 'rejectedBy', 'rejectedReason', 'rejectedAt')->orderBy('updatedAt', 'desc')->get();
            }
        }

        $val = 1;
        foreach ($data as $key) {
            $key->number = $val;
            $val++;
        }

        return $data;
    }

    public function headings(): array
    {
        if (strtolower($this->status) == "pending") {

            return [
                [
                    'No.',
                    'Pemohon',
                    'Jabatan',
                    'Lokasi',
                    'Tipe Cuti',
                    'Tanggal Cuti',
                    'Hari',
                    'Keterangan',
                    'Dibuat Pada'
                ],
            ];
        } elseif (strtolower($this->status) == "approve") {

            return [
                [
                    'No.',
                    'Pemohon',
                    'Jabatan',
                    'Lokasi',
                    'Tipe Cuti',
                    'Tanggal Cuti',
                    'Hari',
                    'Keterangan',
                    'Dibuat Pada',
                    'Diterima Oleh',
                    'Diterima Pada',
                ],
            ];
        } else {

            return [
                [
                    'No.',
                    'Pemohon',
                    'Jabatan',
                    'Lokasi',
                    'Tipe Cuti',
                    'Tanggal Cuti',
                    'Hari',
                    'Keterangan',
                    'Dibuat Pada',
                    'Ditolak Oleh',
                    'Alasan Ditolak',
                    'Ditolak Pada',

                ],
            ];
        }
    }

    public function title(): string
    {
        return 'Leave ' . $this->status;
    }

    public function map($item): array
    {
        $res = null;

        if (strtolower($this->status) == "pending") {

            $res = [
                [
                    $item->number,
                    $item->requester,
                    $item->jobName,
                    $item->locationName,
                    $item->leaveType,
                    $item->date,
                    $item->days,
                    $item->remark,
                    $item->createdAt,
                ],
            ];
        } elseif (strtolower($this->status) == "approve") {

            $res = [
                [
                    $item->number,
                    $item->requester,
                    $item->jobName,
                    $item->locationName,
                    $item->leaveType,
                    $item->date,
                    $item->days,
                    $item->remark,
                    $item->createdAt,
                    $item->approvedBy,
                    $item->approvedAt,

                ],
            ];
        } else {

            $res = [
                [
                    $item->number,
                    $item->requester,
                    $item->jobName,
                    $item->locationName,
                    $item->leaveType,
                    $item->date,
                    $item->days,
                    $item->remark,
                    $item->createdAt,
                    $item->rejectedBy,
                    $item->rejectedReason,
                    $item->rejectedAt,
                ],
            ];
        }

        return $res;
    }
}
