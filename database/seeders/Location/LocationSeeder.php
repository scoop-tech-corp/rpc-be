<?php

namespace Database\Seeders\Location;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('en_US');
        $location = [
            ['codeLocation' => 'ABC1', "locationName" => "RPC ACEH", "status" => 1, "description" => "A pet shop is a business where animals are bought and sold, or kept as pets. There are different types of shop and different types of animals.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC2', "locationName" => "RPC SUMATERA UTARA", "status" => 1, "description" => "A pet shop is a place where people can go to buy animals to keep as pets, to sell, to buy supplies for, or to buy for the purpose of selling", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC3', "locationName" => "RPC SUMATERA BARAT", "status" => 1, "description" => "A pet shop is a place where animals and accessories are sold", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC4', "locationName" => "RPC JAMBI", "status" => 1, "description" => "We're big fans of pets, especially furry ones - so we've put together a list of our favourite pet shops", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC5', "locationName" => "RPC SUMATERA SELATAN", "status" => 1, "description" => "A petshop isn't the easiest thing to make a living from, but there are ways to make it work", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC6', "locationName" => "RPC BENGKULU", "status" => 1, "description" => "Find out what you need to do so you can make your pet shop succeed.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC7', "locationName" => "RPC LAMPUNG", "status" => 1, "description" => "In this guide you will learn about the business of running and maintaining a pet shop.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC8', "locationName" => "RPC KEPULAUAN BANGKA BELITUNG", "status" => 1, "description" => "A pet shop is a business opportunity that allows you to sell pets and pet related products.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC9', "locationName" => "RPC KEPULAUAN RIAU", "status" => 1, "description" => "Running a pet shop is not an easy task, but it's not as difficult as many people think.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC10', "locationName" => "RPC DKI JAKARTA", "status" => 1, "description" => "In this guide you will learn about the business of running and maintaining a pet shop.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

            ['codeLocation' => 'ABC11', "locationName" => "RPC JAWA BARAT", "status" => 1, "description" => "When you have a pet shop business, you need to be ready to manage sick and injured pets, breeders, and customers.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC12', "locationName" => "RPC JAWA TENGAH", "status" => 1, "description" => "A pet shop is a great business idea, as you don't have to have a large amount of capital, and you can make a lot of money.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC13', "locationName" => "RPC DI YOGYAKARTA", "status" => 1, "description" => "A pet shop is an ideal business to start if you're interested in animal care and retail", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC14', "locationName" => "RPC JAWA TIMUR", "status" => 1, "description" => "Learn how to run a pet shop business and how to sell animals online.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC15', "locationName" => "RPC BANTEN", "status" => 1, "description" => "A pet shop is a retail business that sells all types of pet supplies and accessories.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC16', "locationName" => "RPC BALI", "status" => 1, "description" => "It is a high-turnover and low-margin business. This is a detailed beginner's guide.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC17', "locationName" => "RPC NUSA TENGGARA BARAT", "status" => 1, "description" => "Get a running pet shop business up and running quickly. This pet shop business plan will help you get going right away.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC18', "locationName" => "RPC NUSA TENGGARA TIMUR", "status" => 1, "description" => "Running a pet shop is not an easy task! This article will provide you with a good understanding of how to start up a pet shop business.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC19', "locationName" => "RPC KALIMANTAN BARAT", "status" => 1, "description" => "Pet shop business is a great way to make a little extra money and also learn a bit about the pet industry. Here is a guide to running your own pet shop.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC20', "locationName" => "RPC KALIMANTAN TENGAH", "status" => 1, "description" => "Get a running pet shop business up and running quickly", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

            ['codeLocation' => 'ABC21', "locationName" => "RPC KALIMANTAN SELATAN", "status" => 1, "description" => "This pet shop business plan will help you get going right away.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC22', "locationName" => "RPC KALIMANTAN TIMUR", "status" => 1, "description" => "Running a pet shop is not an easy task! This article will provide you with a good understanding of how to start up a pet shop business.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC23', "locationName" => "RPC KALIMANTAN UTARA", "status" => 1, "description" => "Pet shop business is a great way to make a little extra money and also learn a bit about the pet industry.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC24', "locationName" => "RPC SULAWESI UTARA", "status" => 1, "description" => "Here is a guide to running your own pet shop.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC25', "locationName" => "RPC SULAWESI TENGAH", "status" => 1, "description" => "Pet shop business is a great way to make a little extra money and also learn a bit about the pet industry", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC26', "locationName" => "RPC SULAWESI SELATAN", "status" => 1, "description" => "Here is a guide to running your own pet shop.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC27', "locationName" => "RPC SULAWESI TENGGARA", "status" => 1, "description" => "Running a pet shop is not an easy task! This article will provide you with a good understanding of how to start up a pet shop business.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC28', "locationName" => "RPC GORONTALO", "status" => 1, "description" => "Get a running pet shop business up and running quickly. ", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC29', "locationName" => "RPC SULAWESI BARAT", "status" => 1, "description" => "This pet shop business plan will help you get going right away.", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC30', "locationName" => "RPC MALUKU", "status" => 1, "description" => "Get a running pet shop business up and running quickly", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

        ];
        DB::table('location')->insert($location);


        $detailaddress = [
            ['codeLocation' => 'ABC1', "addressName" => "Kp. Cipangwaren RT. 011 RW. 005 Desa Kota Batu, Kecamatan Simeuleu Timur, Kota/Kabupaten Simeulue, Aceh, Indonesia", "additionalInfo" => "Rumah Warna Merah", "provinceCode" => 11, "cityCode" => 1101, "postalCode" => 23891, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC2', "addressName" => "Sunrise Garden Complex No. 8-C,Jl. Surya Mandala", "additionalInfo" => "Rumah Warna Biru", "provinceCode" => 12, "cityCode" => 1201, "postalCode" => 22861, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC3', "addressName" => "Industrial Estate Pulogadung", "additionalInfo" => "Dekat Tukang sate Merdeka", "provinceCode" => 13, "cityCode" => 1301, "postalCode" => 25391, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC4', "addressName" => "Jl. S. Wiryopranoto No. 37, Sawah Besar", "additionalInfo" => "Rumah paling kanan nomor 27", "provinceCode" => 15, "cityCode" => 1501, "postalCode" => 37172, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC5', "addressName" => "Menara Sudirman, 3rd Floor,Jl. Jend. Sudirman Kav. 60", "additionalInfo" => "dekat pertigaan nomor 1", "provinceCode" => 16, "cityCode" => 1601, "postalCode" => 32311, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC6', "addressName" => "Aneka Tambang Building,Jl. Letjen. TB. Simatupang No. 1", "additionalInfo" => "dekat tempat jual nasi goreng", "provinceCode" => 17, "cityCode" => 1701, "postalCode" => 38573, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC7', "addressName" => "Jl. Pemuda No. 296,Jakarta Timur Indonesia", "additionalInfo" => "rumah nomor k 25", "provinceCode" => 18, "cityCode" => 1801, "postalCode" => 34884, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC8', "addressName" => "Glodok Jaya Complex No. 90-91,Jl. Hayam Wuruk No. 100,", "additionalInfo" => "rumah nomor 25", "provinceCode" => 19, "cityCode" => 1901, "postalCode" => 33211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC9', "addressName" => "Jl. HR. Rasuna Said Kav. B-1", "additionalInfo" => "mobil silver 1945 a", "provinceCode" => 21, "cityCode" => 2101, "postalCode" => 29661, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC10', "addressName" => "Wisma Staco, 10th Floor,Jl. Casablanca Kav. 18,", "additionalInfo" => "mobil merah 1888 ab", "provinceCode" => 31, "cityCode" => 3101, "postalCode" => 14540, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

            ['codeLocation' => 'ABC11', "addressName" => "Menara Bank Permata, 3nd Floor,Jl. Jend. Sudirman", "additionalInfo" => "", "provinceCode" => 32, "cityCode" => 3201, "postalCode" => 16810, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC12', "addressName" => "Jl Letjen S Parman Kav 76", "additionalInfo" => "dekat jual jus", "provinceCode" => 33, "cityCode" => 3301, "postalCode" => 53265, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC13', "addressName" => "Jl Jend Sudirman Kav 54-55 Plaza Bapindo Citibank Tower ", "additionalInfo" => "dekat patung", "provinceCode" => 34, "cityCode" => 3401, "postalCode" => 55652, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC14', "addressName" => "Jl Letjen Suprapto Ruko Mega Grosir Cempaka Mas", "additionalInfo" => "blok n nomor 52", "provinceCode" => 35, "cityCode" => 3501, "postalCode" => 63511, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC15', "addressName" => "Jl KH Abdullah Syafei 7 Wisma Laena Lt 2", "additionalInfo" => "blok n nomor 112", "provinceCode" => 36, "cityCode" => 3601, "postalCode" => 42211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC16', "addressName" => "Jl Bukit Gading Raya Rukan Gading Bukit Indah Bl L-26", "additionalInfo" => "blok 123", "provinceCode" => 51, "cityCode" => 5101, "postalCode" => 82211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC17', "addressName" => "Jl Letjen Suprapto 60 Wisma Indra Central Cempaka", "additionalInfo" => "blok a 123", "provinceCode" => 52, "cityCode" => 5201, "postalCode" => 83365, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC18', "addressName" => "Jl Raya Pasar Minggu Kav 34 Graha Sucofindo Bl B", "additionalInfo" => "dekat tukang sate", "provinceCode" => 53, "cityCode" => 5301, "postalCode" => 87211, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC19', "addressName" => "Jl Parang Tritis Raya 3-E,", "additionalInfo" => "dekat KFC", "provinceCode" => 61, "cityCode" => 6101, "postalCode" => 79460, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC20', "addressName" => "Jl Rawa Gelam IV 14 Kawasan Industri", "additionalInfo" => "dekat CFC", "provinceCode" => 62, "cityCode" => 6201, "postalCode" => 74111, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

            ['codeLocation' => 'ABC21', "addressName" => "Jl Jend Sudirman Kav 76-78 Ged Marien Lt 19", "additionalInfo" => "dekat jual martabak", "provinceCode" => 63, "cityCode" => 6301, "postalCode" => 70815, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC22', "addressName" => "Jl Mega Kuningan Lot 5.1 Menara Rajawali Lt 11", "additionalInfo" => "rumah warna merah", "provinceCode" => 64, "cityCode" => 6401, "postalCode" => 76253, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC23', "addressName" => "Jl RS Fatmawati 20 Rukan Fatmawati Mas Bl III", "additionalInfo" => "blok n nomor 52", "provinceCode" => 65, "cityCode" => 6501, "postalCode" => 77550, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC24', "addressName" => "Jl Gondangdia Kecil 12-14 Ged Dana Graha Lt 3,", "additionalInfo" => "dekat jual nasi goreng", "provinceCode" => 71, "cityCode" => 7101, "postalCode" => 95736, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC25', "addressName" => "Jl Jatinegara Tmr I 4,Rawa Bunga", "additionalInfo" => "blok k 45", "provinceCode" => 72, "cityCode" => 7201, "postalCode" => 94881, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC26', "addressName" => "Jl Bendungan Hilir Raya 60 Gunanusa Bldg", "additionalInfo" => "blok z nomor 1", "provinceCode" => 73, "cityCode" => 7301, "postalCode" => 92811, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC27', "addressName" => "Jl Boulevard Artha Gading Rukan", "additionalInfo" => "sebelah gang u", "provinceCode" => 74, "cityCode" => 7401, "postalCode" => 93752, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC28', "addressName" => "Jl Mega Kuningan Tmr Lot 8-9/9,Kuningan", "additionalInfo" => "depan binus syahdan masuk lagi", "provinceCode" => 75, "cityCode" => 7501, "postalCode" => 96262, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC29', "addressName" => "Jl HR Rasuna Said Kav 62 Setiabudi Atrium Lt 4", "additionalInfo" => "sebelah jalan u", "provinceCode" => 76, "cityCode" => 7601, "postalCode" => 91411, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
            ['codeLocation' => 'ABC30', "addressName" => "Jl HR Rasuna Said Kav 62 Setiabudi Atrium Lt 4,", "additionalInfo" => "dekat indomaret", "provinceCode" => 81, "cityCode" => 8101, "postalCode" => 97471, "country" => "Indonesia", "isPrimary" => 1, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],

        ];
        DB::table('location_detail_address')->insert($detailaddress);



        for ($i = 1; $i <= 30; $i++) {


            $code = "ABC" . (string)$i;

            $operationalHour = [
                ["codeLocation" => $code, "dayName" => "Monday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Tuesday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Wednesday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Thursday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Friday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Saturday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
                ["codeLocation" => $code, "dayName" => "Sunday", "fromTime" => "10:00PM", "toTime" => "10:00PM", "allDay" => 1, 'created_at' => now(), 'updated_at' => now(),],
            ];

            DB::table('location_operational')->insert($operationalHour);
        }



        $locationMessengerLoop = $faker->numberBetween($min = 1, $max = 3);
        $isPrimaryMessenger = "Utama";

        for ($j = 1; $j <= 30; $j++) {

            $code = "ABC" . (string)$j;

            $phoneNumber = '(021) ' . $faker->numerify('########'); 

            DB::table('location_messenger')
                ->insert(['codeLocation' => $code, "messengerNumber" => $phoneNumber, "type" => $faker->randomElement(['Office', 'Fax']), "usage" =>  $isPrimaryMessenger, "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),]);

            if ($locationMessengerLoop > 1)
                $isPrimaryMessenger = "Secondary";
        }

        for ($j = 1; $j <= 30; $j++) {

            $code = "ABC" . (string)$j;
            $emaildata = [
                ['codeLocation' => $code, "username" => $faker->email, "usage" => "Utama", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
                ['codeLocation' => $code, "username" => $faker->email, "usage" => "Secondary", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),]
            ];

            DB::table('location_email')->insert($emaildata);
        }

        $locationTelephoneLoop = $faker->numberBetween($min = 1, $max = 3);

        for ($j = 1; $j <= 30; $j++) {

            $code = "ABC" . (string)$j;
            $phoneNumber = $faker->regexify('/^\+628\d{9,10}$/');

            $phone = [
                ['codeLocation' => $code, "phoneNumber" => $phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Utama", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),],
                ['codeLocation' => $code, "phoneNumber" => $phoneNumber, "type" => $faker->randomElement(['Telepon Selular', 'Whatshapp']), "usage" =>  "Secondary", "isDeleted" => 0, 'created_at' => now(), 'updated_at' => now(),]
            ];

            DB::table('location_telephone')->insert($phone);
        }
    }
}
