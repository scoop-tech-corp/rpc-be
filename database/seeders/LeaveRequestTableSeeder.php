<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LeaveRequestTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('leaveRequest')->delete();

        \DB::table('leaveRequest')->insert(array(
            0 =>
            array(
                'id' => 1,
                'usersId' => 1,
                'requesterName' => 'Danny  Wahyudi(danny)',
                'jobTitle' => '1',
                'locationId' => 11,
                'leaveType' => 'Sick Allowance',
                'fromDate' => '2023-03-24',
                'toDate' => '2023-03-24',
                'duration' => 1,
                'workingDays' => 'Friday',
                'status' => 'approve',
                'remark' => 'sick',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 21:45:54',
                'updated_at' => '2023-03-11 23:19:10',
            ),
            1 =>
            array(
                'id' => 2,
                'usersId' => 2,
                'requesterName' => 'Adiyansyah Dwi Putra(Adiyansyah)',
                'jobTitle' => '2',
                'locationId' => 12,
                'leaveType' => 'Sick Allowance',
                'fromDate' => '2023-03-24',
                'toDate' => '2023-03-24',
                'duration' => 1,
                'workingDays' => 'Friday',
                'status' => 'pending',
                'remark' => 'sick',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 21:46:12',
                'updated_at' => '2023-03-11 21:46:12',
            ),
            2 =>
            array(
                'id' => 3,
                'usersId' => 3,
                'requesterName' => 'Johnson Mega Yolo(Supreme)',
                'jobTitle' => '3',
                'locationId' => 13,
                'leaveType' => 'Sick Allowance',
                'fromDate' => '2023-03-24',
                'toDate' => '2023-03-24',
                'duration' => 1,
                'workingDays' => 'Friday',
                'status' => 'pending',
                'remark' => 'sick',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 21:46:16',
                'updated_at' => '2023-03-11 21:46:16',
            ),
            3 =>
            array(
                'id' => 4,
                'usersId' => 4,
                'requesterName' => 'Alucard  (Alucard van helsing)',
                'jobTitle' => '4',
                'locationId' => 14,
                'leaveType' => 'Sick Allowance',
                'fromDate' => '2023-03-24',
                'toDate' => '2023-03-24',
                'duration' => 1,
                'workingDays' => 'Friday',
                'status' => 'reject',
                'remark' => 'sick',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => 'too many request',
                'created_at' => '2023-03-11 21:46:18',
                'updated_at' => '2023-03-11 23:19:40',
            ),
            4 =>
            array(
                'id' => 5,
                'usersId' => 10,
                'requesterName' => 'Krab  Eugene(Mr Krab)',
                'jobTitle' => '4',
                'locationId' => 20,
                'leaveType' => 'Leave Allowance',
                'fromDate' => '2023-03-27',
                'toDate' => '2023-03-28',
                'duration' => 2,
                'workingDays' => 'Monday,Tuesday',
                'status' => 'reject',
                'remark' => 'family matter',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => 'anonymous',
                'created_at' => '2023-03-11 23:03:34',
                'updated_at' => '2023-03-11 23:20:12',
            ),
            5 =>
            array(
                'id' => 6,
                'usersId' => 10,
                'requesterName' => 'Krab  Eugene(Mr Krab)',
                'jobTitle' => '4',
                'locationId' => 20,
                'leaveType' => 'Leave Allowance',
                'fromDate' => '2023-04-06',
                'toDate' => '2023-04-07',
                'duration' => 2,
                'workingDays' => 'Thursday,Friday',
                'status' => 'approve',
                'remark' => 'family matter',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:04:29',
                'updated_at' => '2023-03-11 23:20:21',
            ),
            6 =>
            array(
                'id' => 7,
                'usersId' => 7,
                'requesterName' => 'spongebob  squarepants(Adiyansyah)',
                'jobTitle' => '1',
                'locationId' => 17,
                'leaveType' => 'sick allowance',
                'fromDate' => '2023-04-06',
                'toDate' => '2023-04-06',
                'duration' => 1,
                'workingDays' => 'Thursday',
                'status' => 'pending',
                'remark' => 'medical check up',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:05:18',
                'updated_at' => '2023-03-11 23:05:18',
            ),
            7 =>
            array(
                'id' => 8,
                'usersId' => 4,
                'requesterName' => 'Alucard  (Alucard van helsing)',
                'jobTitle' => '4',
                'locationId' => 14,
                'leaveType' => 'leave allowance',
                'fromDate' => '2023-03-30',
                'toDate' => '2023-03-31',
                'duration' => 2,
                'workingDays' => 'Thursday,Friday',
                'status' => 'pending',
                'remark' => 'pergi singapur',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:06:26',
                'updated_at' => '2023-03-11 23:06:26',
            ),
            8 =>
            array(
                'id' => 9,
                'usersId' => 5,
                'requesterName' => 'clint east wood(clint)',
                'jobTitle' => '2',
                'locationId' => 15,
                'leaveType' => 'leave allowance',
                'fromDate' => '2023-03-30',
                'toDate' => '2023-03-31',
                'duration' => 2,
                'workingDays' => 'Thursday,Friday',
                'status' => 'reject',
                'remark' => 'pergi singapur',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => 'anonymous',
                'created_at' => '2023-03-11 23:06:56',
                'updated_at' => '2023-03-11 23:20:30',
            ),
            9 =>
            array(
                'id' => 10,
                'usersId' => 6,
                'requesterName' => 'squidward testing tenpoles(Adiyansyah)',
                'jobTitle' => '3',
                'locationId' => 16,
                'leaveType' => 'leave allowance',
                'fromDate' => '2023-03-30',
                'toDate' => '2023-03-31',
                'duration' => 2,
                'workingDays' => 'Thursday,Friday',
                'status' => 'pending',
                'remark' => 'pergi singapur',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:06:59',
                'updated_at' => '2023-03-11 23:06:59',
            ),
            10 =>
            array(
                'id' => 11,
                'usersId' => 8,
                'requesterName' => 'Smithy webermen jensen(Adiyansyah)',
                'jobTitle' => '1',
                'locationId' => 18,
                'leaveType' => 'leave allowance',
                'fromDate' => '2023-03-15',
                'toDate' => '2023-03-16',
                'duration' => 2,
                'workingDays' => 'Wednesday,Thursday',
                'status' => 'reject',
                'remark' => 'istirahat',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => 'anonymous',
                'created_at' => '2023-03-11 23:08:04',
                'updated_at' => '2023-03-11 23:21:15',
            ),
            11 =>
            array(
                'id' => 12,
                'usersId' => 8,
                'requesterName' => 'Smithy webermen jensen(Adiyansyah)',
                'jobTitle' => '1',
                'locationId' => 18,
                'leaveType' => 'leave allowance',
                'fromDate' => '2023-03-17',
                'toDate' => '2023-03-17',
                'duration' => 1,
                'workingDays' => 'Friday',
                'status' => 'approve',
                'remark' => 'abisin cuti',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:08:38',
                'updated_at' => '2023-03-11 23:21:23',
            ),
            12 =>
            array(
                'id' => 13,
                'usersId' => 9,
                'requesterName' => 'Patrik  Star(Adiyansyah)',
                'jobTitle' => '2',
                'locationId' => 19,
                'leaveType' => 'sick allowance',
                'fromDate' => '2023-03-20',
                'toDate' => '2023-03-22',
                'duration' => 3,
                'workingDays' => 'Monday,Tuesday,Wednesday',
                'status' => 'approve',
                'remark' => 'abisin cuti',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:09:46',
                'updated_at' => '2023-03-11 23:20:57',
            ),
            13 =>
            array(
                'id' => 14,
                'usersId' => 1,
                'requesterName' => 'Danny  Wahyudi(danny)',
                'jobTitle' => '1',
                'locationId' => 11,
                'leaveType' => 'sick allowance',
                'fromDate' => '2023-03-22',
                'toDate' => '2023-03-22',
                'duration' => 1,
                'workingDays' => 'Wednesday',
                'status' => 'pending',
                'remark' => 'abisin cuti',
                'approveOrRejectedBy' => NULL,
                'approveOrRejectedDate' => NULL,
                'rejectedReason' => NULL,
                'created_at' => '2023-03-11 23:10:42',
                'updated_at' => '2023-03-11 23:10:42',
            ),
            14 =>
            array(
                'id' => 15,
                'usersId' => 1,
                'requesterName' => 'Danny  Wahyudi(danny)',
                'jobTitle' => '1',
                'locationId' => 11,
                'leaveType' => 'sick allowance',
                'fromDate' => '2023-03-23',
                'toDate' => '2023-03-23',
                'duration' => 1,
                'workingDays' => 'Wednesday',
                'status' => 'reject',
                'remark' => 'medical check up rumah sakit',
                'approveOrRejectedBy' => 'Danny  Wahyudi(danny)',
                'approveOrRejectedDate' => '2023-03-11',
                'rejectedReason' => 'anonymous',
                'created_at' => '2023-03-11 23:11:08',
                'updated_at' => '2023-03-11 23:20:36',
            ),
        ));
    }
}
