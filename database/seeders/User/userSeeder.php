<?php

namespace Database\Seeders\User;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class userSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $faker = Faker::create('en_US');
        $jobTitle = [
            ['jobName' => 'Vetenarian', 'isActive' => 1,],
            ['jobName' => 'Doctor', 'isActive' => 1,],
            ['jobName' => 'Receptionist', 'isActive' => 1,],
            ['jobName' => 'Customer', 'isActive' => 1,]
        ];


        $payPeriod = [
            ['periodName' => 'Daily', 'isActive' => 1,],
            ['periodName' => 'Weekly', 'isActive' => 1,],
            ['periodName' => 'Monthly', 'isActive' => 1,]
        ];

        $typeId = [
            ['typeName' => 'NPWP', 'isActive' => 1,],
            ['typeName' => 'KTP', 'isActive' => 1,],
            ['typeName' => 'Passport', 'isActive' => 1,],
        ];



        $users = [
            //1
            [
                'firstName' => 'Danny', 'middleName' => '', 'lastName' => 'Wahyudi', 'nickName' => 'danny', 'gender' => 'male', 'status' => 1, 'locationId' => 11,
                'jobTitleId' => 1, 'startDate' => '2022-12-01', 'endDate' => '2023-11-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '10000000',
                'typeId' => 3, 'identificationNumber' => 1501145032, 'additionalInfo' => 'Testing additional information for inputted database',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 1,
                'isDeleted' => 0,  'created_at' => now(),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '1501145032',  'designation' => '1501145032', 'createdBy' => 'admin', 'email' => 'admin@gmail.com',
            ],

            //2
            [
                'firstName' => 'Adiyansyah', 'middleName' => 'Dwi', 'lastName' => 'Putra', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 1, 'locationId' => 12,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' => 3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 15013534555, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 1,
                'isDeleted' => 0,  'created_at' => now()->addDay(1),  'updated_at' => now(), 'password' => bcrypt("160196"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'danny', 'email' => 'adiyansyahdwiputra@gmail.com',
            ],


            //3
            [
                'firstName' => 'Robbie', 'middleName' => 'Ponce', 'lastName' => '', 'nickName' => 'Ponce', 'gender' => 'male', 'status' => 0, 'locationId' => 13,
                'jobTitleId' => 3, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  1, 'payAmount' =>  '30000000',
                'typeId' => 1, 'identificationNumber' => 15013534555, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 0, 'reminderEmail' => 0, 'reminderWhatsapp' => 0, 'roleId' => 6,
                'isDeleted' => 0,  'created_at' => now()->addDay(2),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '9999999999',  'designation' => '1219835124', 'createdBy' => 'danny', 'email' => 'office@gmail.com',
            ],

            //4
            [
                'firstName' => 'Rose', 'middleName' => '', 'lastName' => '', 'nickName' => 'Rose', 'gender' => 'female', 'status' => 1, 'locationId' => 14,
                'jobTitleId' => 4, 'startDate' => '2022-08-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 1, 'identificationNumber' => 1234567890, 'additionalInfo' => 'Nothing last forever we can change the future',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 7, //add by danny wahyudi
                'isDeleted' => 0,  'created_at' => now()->addDay(3),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '7778889999',  'designation' => '1028492348', 'createdBy' => 'adi', 'email' => 'doctor@gmail.com',
            ],

            //5
            [
                'firstName' => 'Jasper', 'middleName' => '', 'lastName' => 'Saunders', 'nickName' => 'Jasper', 'gender' => 'female', 'status' => 0, 'locationId' => 15,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  1, 'payAmount' =>  '20000000',
                'typeId' => 3, 'identificationNumber' => 298765345678, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 3, //add by danny wahyudi
                'isDeleted' => 0,  'created_at' => now()->addDay(4),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'alucard', 'email' => 'staff@gmail.com',
            ],

            //6
            [
                'firstName' => 'Malika', 'middleName' => '', 'lastName' => 'Oktaviani', 'nickName' => 'Malika', 'gender' => 'female', 'status' => 1, 'locationId' => 16,
                'jobTitleId' => 3, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 627254893472, 'additionalInfo' => 'Klarinet is my way',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 2, //add by danny wahyudi
                'isDeleted' => 0,  'created_at' => now()->addDay(5),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '12446235',  'designation' => '3748346447', 'createdBy' => 'danny', 'email' => 'manager@gmail.com',
            ],

            //7
            [
                'firstName' => 'Sabrina', 'middleName' => '', 'lastName' => 'Palastri', 'nickName' => 'Sabrina', 'gender' => 'female', 'status' => 1, 'locationId' => 17,
                'jobTitleId' => 1, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 15013534555, 'additionalInfo' => 'i love krabby patty',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 4, //add by danny wahyudi
                'isDeleted' => 0,  'created_at' => now()->addDay(6),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '111111111',  'designation' => '222222', 'createdBy' => 'squidward', 'email' => 'customer@gmail.com',
            ],

            //8
            [
                'firstName' => 'Luhung', 'middleName' => '', 'lastName' => 'Samosir', 'nickName' => 'Luhung', 'gender' => 'male', 'status' => 0, 'locationId' => 18,
                'jobTitleId' => 1, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 1111111111, 'additionalInfo' => 'Im number one',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 5, //add by danny wahyudi
                'isDeleted' => 0,  'created_at' => now()->addDay(7),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '1111111111',  'designation' => '1111111111', 'createdBy' => 'spongebob', 'email' => 'internship@gmail.com',
            ],

            //9
            [
                'firstName' => 'Lili', 'middleName' => '', 'lastName' => 'Yolanda ', 'nickName' => 'Yolanda', 'gender' => 'female', 'status' => 1, 'locationId' => 19,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 24729258888, 'additionalInfo' => 'Patrik si bintang laut',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 6,
                'isDeleted' => 0,  'created_at' => now()->addDay(8),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'spongebob', 'email' => 'patrikbintanglaut@gmail.com',
            ],

            //10
            [
                'firstName' => 'Rahmi', 'middleName' => '', 'lastName' => 'Yuniar', 'nickName' => 'Rahmi', 'gender' => 'female', 'status' => 0, 'locationId' => 20,
                'jobTitleId' => 4, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualSickAllowanceRemaining' => 10, 'annualLeaveAllowance' => 10, 'annualLeaveAllowanceRemaining' => 10, 'payPeriodId' =>  1, 'payAmount' =>  '9999999999',
                'typeId' => 2, 'identificationNumber' => 111512312342, 'additionalInfo' => 'uang uang uang uang uang',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 2,
                'isDeleted' => 0,  'created_at' => now()->addDay(9),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '11111127828',  'designation' => '242351234', 'createdBy' => 'danny', 'email' => 'mrkrab@gmail.com',
            ],

        ];


        $userstelephone = [
            //1
            ['usersId' => 1, "phoneNumber" => '087888821648', "type" => 'Telepon Selular', "usage" =>  "Utama", "isDeleted" => 0, 'created_at' => now(),],

            //2
            ['usersId' => 2, "phoneNumber" => '085264992941', "type" => 'Telepon Selular', "usage" =>  "Utama", "isDeleted" => 0, 'created_at' => now(),],
        ];

        $usersEmails = [
            //1
            ['usersId' => 1, "email" => 'admin@gmail.com', "usage" =>  "Utama", "isDeleted" => 0, 'email_verified_at' => now(), 'created_at' => now(),],

            //2
            ['usersId' => 2, "email" => 'adiyansyahdwiputra@gmail.com', "usage" =>  "Utama", "isDeleted" => 0, 'email_verified_at' => now(), 'created_at' => now(),],

            //3
            ['usersId' => 3, "email" => 'office@gmail.com', "usage" =>  "Utama", "isDeleted" => 0, 'email_verified_at' => now(), 'created_at' => now(),],

            //4
            ['usersId' => 4, "email" => 'doctor@gmail.com', "usage" =>  "Utama", "isDeleted" => 0, 'email_verified_at' => now(), 'created_at' => now(),],
            //5
            ['usersId' => 5, "email" => 'staff@gmail.com', "usage" =>  "Utama", "isDeleted" => 0, 'email_verified_at' => now(), 'created_at' => now(),],

        ];

        $usersmessengers = [
            //1
            ["usersId" => 1, "messengerNumber" => '085265779499', "type" => 'Office', "usage" =>  'Utama', "isDeleted" => 0, 'created_at' => now(),],

            //2
            ["usersId" => 2, "messengerNumber" => '081501035232', "type" => 'Office', "usage" =>  'Utama', "isDeleted" => 0, 'created_at' => now(),],
        ];


        $detailaddress = [
            ['usersId' => '1', "addressName" => "Kp. Cipangwaren RT. 011 RW. 005 Desa Kota Batu, Kecamatan Simeuleu Timur, Kota/Kabupaten Simeulue, Aceh, Indonesia", "additionalInfo" => "Rumah Warna Merah", "provinceCode" => 11, "cityCode" => 1101, "postalCode" => 23891, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '2', "addressName" => "Sunrise Garden Complex No. 8-C,Jl. Surya Mandala", "additionalInfo" => "Rumah Warna Biru", "provinceCode" => 12, "cityCode" => 1201, "postalCode" => 22861, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '3', "addressName" => "Industrial Estate Pulogadung", "additionalInfo" => "Dekat Tukang sate Merdeka", "provinceCode" => 13, "cityCode" => 1301, "postalCode" => 25391, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '4', "addressName" => "Jl. S. Wiryopranoto No. 37, Sawah Besar", "additionalInfo" => "Rumah paling kanan nomor 27", "provinceCode" => 15, "cityCode" => 1501, "postalCode" => 37172, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '5', "addressName" => "Menara Sudirman, 3rd Floor,Jl. Jend. Sudirman Kav. 60", "additionalInfo" => "dekat pertigaan nomor 1", "provinceCode" => 16, "cityCode" => 1601, "postalCode" => 32311, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '6', "addressName" => "Aneka Tambang Building,Jl. Letjen. TB. Simatupang No. 1", "additionalInfo" => "dekat tempat jual nasi goreng", "provinceCode" => 17, "cityCode" => 1701, "postalCode" => 38573, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '7', "addressName" => "Jl. Pemuda No. 296,Jakarta Timur Indonesia", "additionalInfo" => "rumah nomor k 25", "provinceCode" => 18, "cityCode" => 1801, "postalCode" => 34884, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '8', "addressName" => "Glodok Jaya Complex No. 90-91,Jl. Hayam Wuruk No. 100,", "additionalInfo" => "rumah nomor 25", "provinceCode" => 19, "cityCode" => 1901, "postalCode" => 33211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '9', "addressName" => "Jl. HR. Rasuna Said Kav. B-1", "additionalInfo" => "mobil silver 1945 a", "provinceCode" => 21, "cityCode" => 2101, "postalCode" => 29661, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
            ['usersId' => '10', "addressName" => "Wisma Staco, 10th Floor,Jl. Casablanca Kav. 18,", "additionalInfo" => "mobil merah 1888 ab", "provinceCode" => 31, "cityCode" => 3101, "postalCode" => 14540, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        ];


        for ($j = 3; $j <= 10; $j++) {

            $phoneNumber = $faker->regexify('/^\+628\d{9,10}$/');

            $phone = [
                ["usersId" => $j, "phoneNumber" => $phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Utama", "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "phoneNumber" => $phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Secondary", "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('usersTelephones')->insert($phone);
        }


        for ($j = 3; $j <= 10; $j++) {

            $phoneNumber = '(021) ' . $faker->numerify('########');

            $messenger = [
                ["usersId" => $j, "messengerNumber" => $phoneNumber, "type" => $faker->randomElement(['Office', 'Fax']), "usage" =>  'Utama', "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "messengerNumber" => $phoneNumber, "type" => $faker->randomElement(['Office', 'Fax']), "usage" =>  'Secondary', "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('usersMessengers')->insert($messenger);
        }


        for ($j = 6; $j <= 10; $j++) {

            $emaildata = [
                ["usersId" => $j, "email" => $faker->email, 'email_verified_at' => now(), "usage" => "Utama", "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "email" => $faker->email, 'email_verified_at' => now(), "usage" => "Secondary", "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('usersEmails')->insert($emaildata);
        }

        DB::table('users')->insert($users);
        DB::table('usersTelephones')->insert($userstelephone);
        DB::table('usersMessengers')->insert($usersmessengers);
        DB::table('usersEmails')->insert($usersEmails);
        DB::table('usersDetailAddresses')->insert($detailaddress);
        DB::table('jobTitle')->insert($jobTitle);
        DB::table('payPeriod')->insert($payPeriod);
        DB::table('typeId')->insert($typeId);


    }
}
