<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class testing extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

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


        // $dataUser = [
        //     [
        //         'name' => 'Danny Wahyudi',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Single",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan U 27 b Palmerah Barat nomor 76",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "26-01-1993",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "1",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => "wahyudidanny23@gmail.com",
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("123"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],




        //     [
        //         'name' => 'DW',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Single",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan U 27 b Palmerah Barat nomor 76",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "26-01-1993",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "1",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => 'admin@gmail.com',
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("123"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],

        //     [
        //         'name' => 'DW',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Single",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan U 27 b Palmerah Barat nomor 76",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "26-01-1993",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "1",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => "office@gmail.com",
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("123"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],

        //     [
        //         'name' => 'DW',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Single",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan U 27 b Palmerah Barat nomor 76",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "26-01-1993",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "1",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => 'staff@gmail.com',
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("123"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],

        //     [
        //         'name' => 'DW',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Single",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan U 27 b Palmerah Barat nomor 76",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "26-01-1993",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "1",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => 'doctor@gmail.com',
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("123"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],


        //     [
        //         'name' => 'Adiyansyah Dwi Putra',
        //         'gender' => 'Male',
        //         'maritalStatus' => "Married",
        //         'citizen' => "Indonesian",
        //         'address' => "Jalan Anggur no 12 Binus Syahdan",
        //         'lastEducation' => "S1",
        //         'placeOfBirth' => "Jakarta",
        //         'dateOfBirth' => "12-12-1997",
        //         'idCardNumber' => "3602041211870001",
        //         'npwp' => "08.178.554.2-123.321",
        //         'locationId' => "2",
        //         'role' => "1",
        //         'yearsExperience' => "10",
        //         'phonenumber' => "087888821648",
        //         'email' => "adiyansyahdwiputra@gmail.com",
        //         'additionalInfo' => "tidak ada additional info",
        //         'password' => bcrypt("160196"),
        //         'isDeleted' => '0',
        //         'created_at' => now()
        //     ],

        // ];




        $dataUser = [
            
            [
                'firstName' => 'Danny','middleName' => '','lastName' => 'Wahyudi','nickName' => 'danny', 'gender' => 'male', 'status' => 1,
                'role' => 1 , 'startDate' => '10-12-2022', 'endDate' => '11-12-2022',
                'annualSickAllowance' => 10 , 'annualLeaveAllowance' => 10 , 'payPeriod' =>  'Monthly', 'payAmount' =>  10000000, 
                'typeId' => 'KTP' , 'identificationNumber' => 1501145032 , 'additionalInfo' => 'Testing additional information for inputted database' ,
                'generalCustomerCanSchedule' => 1 , 'generalCustomerReceiveDailyEmail' => 1 , 'generalAllowMemberToLogUsingEmail' => 1, 'reminderEmail' => 1 , 'reminderWhatsapp' => 1, 'securityGroupAdmin' => 1 , 'securityGroupManager' => 1, 'securityGroupVet' => 1,  'securityGroupReceptionist' => 1,
                'isDeleted' => 0,  'created_at' =>now(),  'updated_at' =>now(), 'password' => bcrypt("123"),
            ]

        ];

        DB::table('users')->insert($dataUser);




        // DB::table('users')->insert([
        //     'name' => 'DW',
        //     'email' => 'office@gmail.com',
        //     'password' => bcrypt("123"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);



        // DB::table('users')->insert([
        //     'name' => 'DW',
        //     'email' => 'wahyudidanny23@gmail.com',
        //     'password' => bcrypt("123"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);


        // DB::table('users')->insert([
        //     'name' => 'DW',
        //     'email' => 'admin@gmail.com',
        //     'password' => bcrypt("123"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);


        // DB::table('users')->insert([
        //     'name' => 'DW',
        //     'email' => 'doctor@gmail.com',
        //     'password' => bcrypt("123"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);

        // DB::table('users')->insert([
        //     'name' => 'DW',
        //     'email' => 'staff@gmail.com',
        //     'password' => bcrypt("123"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);


        // DB::table('users')->insert([
        //     'name' => 'Adiyansyah Dwi Putra',
        //     'email' => 'adiyansyahdwiputra@gmail.com',
        //     'password' => bcrypt("160196"),
        //     'role' => '1',
        //     'isDeleted' => '0',
        //     'created_at' =>now(),
        //     'updated_at' =>now(),
        // ]);


    }
}
