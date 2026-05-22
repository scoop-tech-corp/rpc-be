<?php

namespace App\Services;

use App\Models\User;
use App\Models\staffcontract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendEmail;

class StaffService
{
    public function createStaff(array $data, $user, $files)
    {
        return DB::transaction(function () use ($data, $user, $files) {
            $start = Carbon::parse($data['startDate']);
            $end = Carbon::parse($data['endDate']);

            // Find primary email
            $primaryEmail = '';
            foreach ($data['email_array'] as $email) {
                if (strtolower($email['usage']) == 'utama' || strtolower($email['usage']) == 'primary') {
                    $primaryEmail = $email['email'];
                    break;
                }
            }

            // Create User using Eloquent
            $newUser = User::create([
                'firstName' => $data['firstName'],
                'middleName' => $data['middleName'] ?? null,
                'lastName' => $data['lastName'] ?? null,
                'nickName' => $data['nickName'] ?? null,
                'gender' => $data['gender'] ?? null,
                'status' => $data['status'],
                'lineManagerId' => $data['lineManagerId'],
                'jobTitleId' => $data['jobTitleId'],
                'startDate' => $start,
                'endDate' => $end,
                'joinDate' => $start,
                'registrationNo' => $data['registrationNo'] ?? null,
                'designation' => $data['designation'] ?? null,
                'annualSickAllowance' => $data['annualSickAllowance'] ?? 0,
                'annualSickAllowanceRemaining' => $data['annualSickAllowance'] ?? 0,
                'annualLeaveAllowance' => $data['annualLeaveAllowance'] ?? 0,
                'annualLeaveAllowanceRemaining' => $data['annualLeaveAllowance'] ?? 0,
                'payPeriodId' => $data['payPeriodId'],
                'payAmount' => $data['payAmount'] ?? 0,
                'typeId' => 0,
                'identificationNumber' => '',
                'additionalInfo' => $data['additionalInfo'] ?? null,
                'generalCustomerCanSchedule' => $data['generalCustomerCanSchedule'] ?? 0,
                'generalCustomerReceiveDailyEmail' => $data['generalCustomerReceiveDailyEmail'] ?? 0,
                'generalAllowMemberToLogUsingEmail' => $data['generalAllowMemberToLogUsingEmail'] ?? 0,
                'reminderEmail' => $data['reminderEmail'] ?? 0,
                'reminderWhatsapp' => $data['reminderWhatsapp'] ?? 0,
                'roleId' => $data['roleId'] ?? 0,
                'isDeleted' => 0,
                'createdBy' => $user->firstName,
                'email' => $primaryEmail,
                'isLogin' => 0,
            ]);

            $lastInsertedID = $newUser->id;

            recentActivity(
                $user->id,
                'Staff',
                'Add Staff',
                'Add new staff'
            );

            staffcontract::create([
                'staffId' => $lastInsertedID,
                'startDate' => $start,
                'endDate' => $end,
                'userId' => $user->id,
            ]);

            if (!empty($data['locationId_array'])) {
                $locations = [];
                foreach ($data['locationId_array'] as $loc) {
                    $locations[] = [
                        'usersId' => $lastInsertedID,
                        'locationId' => $loc,
                        'isMainLocation' => 1,
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('usersLocation')->insert($locations);
            }

            if (!empty($data['detailAddress_array'])) {
                $addresses = [];
                foreach ($data['detailAddress_array'] as $val) {
                    $addresses[] = [
                        'usersId' => $lastInsertedID,
                        'addressName' => $val['addressName'],
                        'additionalInfo' => $val['additionalInfo'] ?? '',
                        'provinceCode' => $val['provinceCode'],
                        'cityCode' => $val['cityCode'],
                        'postalCode' => $val['postalCode'] ?? '',
                        'country' => $val['country'],
                        'isPrimary' => $val['isPrimary'] ?? 0,
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('usersDetailAddresses')->insert($addresses);
            }

            // Identifications Images handling
            if (!empty($data['typeIdentifications_array']) && !empty($files)) {
                $identifications = [];
                $count = 0;
                
                foreach ($files as $file) {
                    foreach ($file as $fil) {
                        $name = $fil->hashName();
                        $fil->move(public_path() . '/UsersIdentificationImages/', $name);
                        $fileName = "/UsersIdentificationImages/" . $name;

                        $identifications[] = [
                            'usersId' => $lastInsertedID,
                            'typeId' => $data['typeIdentifications_array'][$count]['typeId'],
                            'identification' => $data['typeIdentifications_array'][$count]['identificationNumber'],
                            'imagePath' => $fileName,
                            'status' => 1,
                            'reason' => '',
                            'approvedBy' => $user->id,
                            'approvedAt' => now(),
                            'isDeleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $count++;
                    }
                }
                if (!empty($identifications)) {
                    DB::table('usersIdentifications')->insert($identifications);
                }
            }

            if (!empty($data['messenger_array'])) {
                $messengers = [];
                foreach ($data['messenger_array'] as $val) {
                    $messengers[] = [
                        'usersId' => $lastInsertedID,
                        'messengerNumber' => $val['messengerNumber'],
                        'type' => $val['type'],
                        'usage' => $val['usage'],
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('usersMessengers')->insert($messengers);
            }

            if (!empty($data['email_array'])) {
                $emails = [];
                foreach ($data['email_array'] as $val) {
                    $emails[] = [
                        'usersId' => $lastInsertedID,
                        'email' => $val['email'],
                        'usage' => $val['usage'],
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('usersEmails')->insert($emails);
            }

            if (!empty($data['telephone_array'])) {
                $telephones = [];
                foreach ($data['telephone_array'] as $val) {
                    $telephones[] = [
                        'usersId' => $lastInsertedID,
                        'phoneNumber' => $val['phoneNumber'],
                        'type' => $val['type'],
                        'usage' => $val['usage'],
                        'isDeleted' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('usersTelephones')->insert($telephones);
            }

            // Send Email if status == 1
            if ($data['status'] == 1) {
                $nameParts = array_filter([
                    $newUser->firstName,
                    $newUser->middleName,
                    $newUser->lastName,
                    $newUser->nickName ? '(' . $newUser->nickName . ')' : null
                ]);
                $fullName = implode(' ', $nameParts);

                $jobTitle = DB::table('jobTitle')
                    ->where('id', $data['jobTitleId'])
                    ->where('isActive', 1)
                    ->value('jobName');

                $emailData = [
                    'subject' => 'RPC Petshop',
                    'body' => 'Please verify your account',
                    'isi' => 'This e-mail was sent from a notification-only address that cannot accept incoming e-mails. Please do not reply to this message.',
                    'name' => $fullName,
                    'email' => $primaryEmail,
                    'jobTitle' => $jobTitle ?? '',
                    'usersId' => $lastInsertedID,
                ];

                Mail::to($primaryEmail)->send(new SendEmail($emailData));
            }

            return $newUser;
        });
    }
}
