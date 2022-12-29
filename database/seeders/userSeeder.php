<?php

namespace Database\Seeders;

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

        $data = [
            [
                'value' => 'Usage',
                'name' => 'Utama',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Usage',
                'name' => 'Secondary',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Usage',
                'name' => 'Whatsap',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Telephone',
                'name' => 'Whatshap',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Telephone',
                'name' => 'Rumah',
                'isDeleted' => 0,
                'created_at' => now()
            ],

            [
                'value' => 'Messenger',
                'name' => 'Gmail',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'Yahoo',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'GooglePlus',
                'isDeleted' => 0,
                'created_at' => now()
            ],
            [
                'value' => 'Messenger',
                'name' => 'Bing',
                'isDeleted' => 0,
                'created_at' => now()
            ],
        ];

        DB::table('data_static')->insert($data);

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
                'annualSickAllowance' => 9, 'annualLeaveAllowance' => 9, 'payPeriodId' =>  2, 'payAmount' =>  '10000000',
                'typeId' => 3, 'identificationNumber' => 1501145032, 'additionalInfo' => 'Testing additional information for inputted database',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 1,
                'isDeleted' => 0,  'created_at' => now(),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '1501145032',  'designation' => '1501145032', 'createdBy' => 'admin', 'email' => 'admin@gmail.com',
            ],

            //2
            [
                'firstName' => 'Adiyansyah', 'middleName' => 'Dwi', 'lastName' => 'Putra', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 1, 'locationId' => 12,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' => 3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 15013534555, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 2,
                'isDeleted' => 0,  'created_at' => now()->addDay(1),  'updated_at' => now(), 'password' => bcrypt("160196"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'danny', 'email' => 'adiyansyahdwiputra@gmail.com',
            ],



  


            //3
            [
                'firstName' => 'Johnson', 'middleName' => 'Mega', 'lastName' => 'Yolo', 'nickName' => 'Supreme', 'gender' => 'male', 'status' => 0, 'locationId' => 13,
                'jobTitleId' => 3, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 100, 'annualLeaveAllowance' => 200, 'payPeriodId' =>  1, 'payAmount' =>  '30000000',
                'typeId' => 1, 'identificationNumber' => 15013534555, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 0, 'reminderEmail' => 0, 'reminderWhatsapp' => 0, 'roleId' => 3,
                'isDeleted' => 0,  'created_at' => now()->addDay(2),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '9999999999',  'designation' => '1219835124', 'createdBy' => 'danny', 'email' => 'office@gmail.com',
            ],

            //4
            [
                'firstName' => 'Alucard', 'middleName' => '', 'lastName' => '', 'nickName' => 'Alucard van helsing', 'gender' => 'male', 'status' => 1, 'locationId' => 14,
                'jobTitleId' => 4, 'startDate' => '2022-08-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 1, 'identificationNumber' => 1234567890, 'additionalInfo' => 'Nothing last forever we can change the future',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 1,
                'isDeleted' => 0,  'created_at' => now()->addDay(3),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '7778889999',  'designation' => '1028492348', 'createdBy' => 'adi', 'email' => 'doctor@gmail.com',
            ],

            //5
            [
                'firstName' => 'clint', 'middleName' => 'east', 'lastName' => 'wood', 'nickName' => 'clint', 'gender' => 'male', 'status' => 0, 'locationId' => 15,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  1, 'payAmount' =>  '20000000',
                'typeId' => 3, 'identificationNumber' => 298765345678, 'additionalInfo' => 'Your additional information RPC petshop care',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 2,
                'isDeleted' => 0,  'created_at' => now()->addDay(4),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'alucard', 'email' => 'staff@gmail.com',
            ],

            //6
            [
                'firstName' => 'squidward', 'middleName' => 'testing', 'lastName' => 'tenpoles', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 1, 'locationId' => 16,
                'jobTitleId' => 3, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 627254893472, 'additionalInfo' => 'Klarinet is my way',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 3,
                'isDeleted' => 0,  'created_at' => now()->addDay(5),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '12446235',  'designation' => '3748346447', 'createdBy' => 'danny', 'email' => 'squidwardofficial@gmail.com',
            ],

            //7
            [
                'firstName' => 'spongebob', 'middleName' => '', 'lastName' => 'squarepants', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 1, 'locationId' => 17,
                'jobTitleId' => 1, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 15013534555, 'additionalInfo' => 'i love krabby patty',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 1,
                'isDeleted' => 0,  'created_at' => now()->addDay(6),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '111111111',  'designation' => '222222', 'createdBy' => 'squidward', 'email' => 'squarepants@gmail.com',
            ],

            //8
            [
                'firstName' => 'Smithy', 'middleName' => 'webermen', 'lastName' => 'jensen', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 0, 'locationId' => 18,
                'jobTitleId' => 1, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 10, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  3, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 1111111111, 'additionalInfo' => 'Im number one',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 4,
                'isDeleted' => 0,  'created_at' => now()->addDay(7),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '1111111111',  'designation' => '1111111111', 'createdBy' => 'spongebob', 'email' => 'smithywebermenjensen@gmail.com',
            ],

            //9
            [
                'firstName' => 'Patrik', 'middleName' => '', 'lastName' => 'Star', 'nickName' => 'Adiyansyah', 'gender' => 'male', 'status' => 1, 'locationId' => 19,
                'jobTitleId' => 2, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 100, 'annualLeaveAllowance' => 10, 'payPeriodId' =>  2, 'payAmount' =>  '20000000',
                'typeId' => 2, 'identificationNumber' => 24729258888, 'additionalInfo' => 'Patrik si bintang laut',
                'generalCustomerCanSchedule' => 1, 'generalCustomerReceiveDailyEmail' => 1, 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1, 'reminderWhatsapp' => 1,  'roleId' => 6,
                'isDeleted' => 0,  'created_at' => now()->addDay(8),  'updated_at' => now(), 'password' => bcrypt("123"),
                'registrationNo' => '8782784881',  'designation' => '1219835124', 'createdBy' => 'spongebob', 'email' => 'patrikbintanglaut@gmail.com',
            ],

            //10
            [
                'firstName' => 'Krab', 'middleName' => '', 'lastName' => 'Eugene', 'nickName' => 'Mr Krab', 'gender' => 'male', 'status' => 0, 'locationId' => 20,
                'jobTitleId' => 4, 'startDate' => '2022-12-01', 'endDate' => '2023-12-02',
                'annualSickAllowance' => 100000, 'annualLeaveAllowance' => 100000, 'payPeriodId' =>  1, 'payAmount' =>  '9999999999',
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

            $phone = [
                ["usersId" => $j, "phoneNumber" => $faker->phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Utama", "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "phoneNumber" => $faker->phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Secondary", "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('userstelephones')->insert($phone);
        }


        for ($j = 3; $j <= 10; $j++) {

            $messenger = [
                ["usersId" => $j, "messengerNumber" => $faker->phoneNumber, "type" => $faker->randomElement(['Office', 'Fax']), "usage" =>  'Utama', "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "messengerNumber" => $faker->phoneNumber, "type" => $faker->randomElement(['Office', 'Fax']), "usage" =>  'Secondary', "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('usersmessengers')->insert($messenger);
        }


        for ($j = 6; $j <= 10; $j++) {

            $emaildata = [
                ["usersId" => $j, "email" => $faker->email, 'email_verified_at' => now(), "usage" => "Utama", "isDeleted" => 0, 'created_at' => now(),],
                ["usersId" => $j, "email" => $faker->email, 'email_verified_at' => now(), "usage" => "Secondary", "isDeleted" => 0, 'created_at' => now(),]
            ];

            DB::table('usersEmails')->insert($emaildata);
        }

        DB::table('users')->insert($users);
        DB::table('userstelephones')->insert($userstelephone);
        DB::table('usersmessengers')->insert($usersmessengers);
        DB::table('usersEmails')->insert($usersEmails);
        DB::table('usersdetailaddresses')->insert($detailaddress);
        DB::table('jobTitle')->insert($jobTitle);
        DB::table('payPeriod')->insert($payPeriod);
        DB::table('typeId')->insert($typeId);


        // //dummy
        // $detailaddress = [
        // 	['usersId' => '1', "addressName" => "Kp. Cipangwaren RT. 011 RW. 005 Desa Kota Batu, Kecamatan Simeuleu Timur, Kota/Kabupaten Simeulue, Aceh, Indonesia", "additionalInfo" => "Rumah Warna Merah", "provinceCode" => 11, "cityCode" => 1101, "postalCode" => 23891, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '2', "addressName" => "Sunrise Garden Complex No. 8-C,Jl. Surya Mandala", "additionalInfo" => "Rumah Warna Biru", "provinceCode" => 12, "cityCode" => 1201, "postalCode" => 22861, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '3', "addressName" => "Industrial Estate Pulogadung", "additionalInfo" => "Dekat Tukang sate Merdeka", "provinceCode" => 13, "cityCode" => 1301, "postalCode" => 25391, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '4', "addressName" => "Jl. S. Wiryopranoto No. 37, Sawah Besar", "additionalInfo" => "Rumah paling kanan nomor 27", "provinceCode" => 15, "cityCode" => 1501, "postalCode" => 37172, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '5', "addressName" => "Menara Sudirman, 3rd Floor,Jl. Jend. Sudirman Kav. 60", "additionalInfo" => "dekat pertigaan nomor 1", "provinceCode" => 16, "cityCode" => 1601, "postalCode" => 32311, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '6', "addressName" => "Aneka Tambang Building,Jl. Letjen. TB. Simatupang No. 1", "additionalInfo" => "dekat tempat jual nasi goreng", "provinceCode" => 17, "cityCode" => 1701, "postalCode" => 38573, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '7', "addressName" => "Jl. Pemuda No. 296,Jakarta Timur Indonesia", "additionalInfo" => "rumah nomor k 25", "provinceCode" => 18, "cityCode" => 1801, "postalCode" => 34884, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '8', "addressName" => "Glodok Jaya Complex No. 90-91,Jl. Hayam Wuruk No. 100,", "additionalInfo" => "rumah nomor 25", "provinceCode" => 19, "cityCode" => 1901, "postalCode" => 33211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '9', "addressName" => "Jl. HR. Rasuna Said Kav. B-1", "additionalInfo" => "mobil silver 1945 a", "provinceCode" => 21, "cityCode" => 2101, "postalCode" => 29661, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '10', "addressName" => "Wisma Staco, 10th Floor,Jl. Casablanca Kav. 18,", "additionalInfo" => "mobil merah 1888 ab", "provinceCode" => 31, "cityCode" => 3101, "postalCode" => 14540, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],

        // 	['usersId' => '11', "addressName" => "Menara Bank Permata, 3nd Floor,Jl. Jend. Sudirman", "additionalInfo" => "", "provinceCode" => 32, "cityCode" => 3201, "postalCode" => 16810, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '12', "addressName" => "Jl Letjen S Parman Kav 76", "additionalInfo" => "dekat jual jus", "provinceCode" => 33, "cityCode" => 3301, "postalCode" => 53265, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '13', "addressName" => "Jl Jend Sudirman Kav 54-55 Plaza Bapindo Citibank Tower ", "additionalInfo" => "dekat patung", "provinceCode" => 34, "cityCode" => 3401, "postalCode" => 55652, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '14', "addressName" => "Jl Letjen Suprapto Ruko Mega Grosir Cempaka Mas", "additionalInfo" => "blok n nomor 52", "provinceCode" => 35, "cityCode" => 3501, "postalCode" => 63511, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '15', "addressName" => "Jl KH Abdullah Syafei 7 Wisma Laena Lt 2", "additionalInfo" => "blok n nomor 112", "provinceCode" => 36, "cityCode" => 3601, "postalCode" => 42211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '16', "addressName" => "Jl Bukit Gading Raya Rukan Gading Bukit Indah Bl L-26", "additionalInfo" => "blok 123", "provinceCode" => 51, "cityCode" => 5101, "postalCode" => 82211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '17', "addressName" => "Jl Letjen Suprapto 60 Wisma Indra Central Cempaka", "additionalInfo" => "blok a 123", "provinceCode" => 52, "cityCode" => 5201, "postalCode" => 83365, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '18', "addressName" => "Jl Raya Pasar Minggu Kav 34 Graha Sucofindo Bl B", "additionalInfo" => "dekat tukang sate", "provinceCode" => 53, "cityCode" => 5301, "postalCode" => 87211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '19', "addressName" => "Jl Parang Tritis Raya 3-E,", "additionalInfo" => "dekat KFC", "provinceCode" => 61, "cityCode" => 6101, "postalCode" => 79460, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '20', "addressName" => "Jl Rawa Gelam IV 14 Kawasan Industri", "additionalInfo" => "dekat CFC", "provinceCode" => 62, "cityCode" => 6201, "postalCode" => 74111, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],

        // 	['usersId' => '21', "addressName" => "Jl Jend Sudirman Kav 76-78 Ged Marien Lt 19", "additionalInfo" => "dekat jual martabak", "provinceCode" => 63, "cityCode" => 6301, "postalCode" => 70815, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '22', "addressName" => "Jl Mega Kuningan Lot 5.1 Menara Rajawali Lt 11", "additionalInfo" => "rumah warna merah", "provinceCode" => 64, "cityCode" => 6401, "postalCode" => 76253, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '23', "addressName" => "Jl RS Fatmawati 20 Rukan Fatmawati Mas Bl III", "additionalInfo" => "blok n nomor 52", "provinceCode" => 65, "cityCode" => 6501, "postalCode" => 77550, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '24', "addressName" => "Jl Gondangdia Kecil 12-14 Ged Dana Graha Lt 3,", "additionalInfo" => "dekat jual nasi goreng", "provinceCode" => 71, "cityCode" => 7101, "postalCode" => 95736, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '25', "addressName" => "Jl Jatinegara Tmr I 4,Rawa Bunga", "additionalInfo" => "blok k 45", "provinceCode" => 72, "cityCode" => 7201, "postalCode" => 94881, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '26', "addressName" => "Jl Bendungan Hilir Raya 60 Gunanusa Bldg", "additionalInfo" => "blok z nomor 1", "provinceCode" => 73, "cityCode" => 7301, "postalCode" => 92811, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '27', "addressName" => "Jl Boulevard Artha Gading Rukan", "additionalInfo" => "sebelah gang u", "provinceCode" => 74, "cityCode" => 7401, "postalCode" => 93752, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '28', "addressName" => "Jl Mega Kuningan Tmr Lot 8-9/9,Kuningan", "additionalInfo" => "depan binus syahdan masuk lagi", "provinceCode" => 75, "cityCode" => 7501, "postalCode" => 96262, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '29', "addressName" => "Jl HR Rasuna Said Kav 62 Setiabudi Atrium Lt 4", "additionalInfo" => "sebelah jalan u", "provinceCode" => 76, "cityCode" => 7601, "postalCode" => 91411, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],
        // 	['usersId' => '30', "addressName" => "Jl HR Rasuna Said Kav 62 Setiabudi Atrium Lt 4,", "additionalInfo" => "dekat indomaret", "provinceCode" => 81, "cityCode" => 8101, "postalCode" => 97471, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(),],

        // ];
        // DB::table('usersdetailaddresses')->insert($detailaddress);





    }
}
