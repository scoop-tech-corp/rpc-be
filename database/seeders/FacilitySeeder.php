<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('en_US');
        $facility = [
          
            [ 'locationId' => '1',"locationName" => "RPC ACEH","introduction"=> "Motivational pet shop","description"=>"full range of motivators and a range of different items to suit all budgets.","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '2',"locationName" => "RPC SUMATERA UTARA","introduction"=> "A pet shop is a place where people can buy or trade things like pets.","description"=>"Pet shops can be found in most major cities.","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '3',"locationName" => "RPC SUMATERA BARAT","introduction"=> "The first step to setting yourself up for success is finding something that you're passionate about","description"=>"Inspirational, motivational and motivational","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '4',"locationName" => "RPC JAMBI","introduction"=> "find a great pet for your family ","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '5',"locationName" => "RPC SUMATERA SELATAN","introduction"=> "This shop is for all your pet supplies. ","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '6',"locationName" => "RPC BENGKULU","introduction"=> "Motivation is a funny thing. It can start from a small spark and just keep growing","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '7',"locationName" => "RPC LAMPUNG","introduction"=> "This shop is for all your pet supplies. ","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '8',"locationName" => "RPC KEPULAUAN BANGKA BELITUNG","introduction"=> "This is a guide to encourage and motivate the pet shop person to do their job well.","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '9',"locationName" => "RPC KEPULAUAN RIAU","introduction"=> "This shop is for all your pet supplies. ","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '10',"locationName" =>"RPC DKI JAKARTA","introduction"=> "A pet shop is a place where people can purchase and sell animals or items relating to them.","description"=>"","isDeleted"=>0,'created_at' =>now(),],
           
  		    [ 'locationId' => '11',"locationName" =>"RPC JAWA BARAT","introduction"=> "This is a guide to encourage and motivate the pet shop person to do their job well.","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '12',"locationName" =>"RPC JAWA TENGAH","introduction"=> "A pet shop is a place where people can purchase and sell animals or items relating to them.","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '13',"locationName" =>"RPC DI YOGYAKARTA","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '14',"locationName" =>"RPC JAWA TIMUR","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '15',"locationName" =>"RPC BANTEN","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '16',"locationName" =>"RPC BALI","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '17',"locationName" =>"RPC NUSA TENGGARA BARAT","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '18',"locationName" =>"RPC NUSA TENGGARA TIMUR","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '19',"locationName" =>"RPC KALIMANTAN BARAT","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '20',"locationName" =>"RPC KALIMANTAN TENGAH","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            
			[ 'locationId' => '21',"locationName" =>"RPC KALIMANTAN SELATAN","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '22',"locationName" =>"RPC KALIMANTAN TIMUR","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '23',"locationName" =>"RPC KALIMANTAN UTARA","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '24',"locationName" =>"RPC SULAWESI UTARA","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '25',"locationName" =>"RPC SULAWESI TENGAH","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '26',"locationName" =>"RPC SULAWESI SELATAN","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '27',"locationName" =>"RPC SULAWESI TENGGARA","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '28',"locationName" =>"RPC GORONTALO","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '29',"locationName" =>"RPC SULAWESI BARAT","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            [ 'locationId' => '30',"locationName" =>"RPC MALUKU","introduction"=> "","description"=>"","isDeleted"=>0,'created_at' =>now(),],
            
        ];	

            DB::table('facility')->insert($facility); 



            $facilityunit = [
          
                [ 'locationId' => '1',"locationName" => "RPC ACEH","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '1',"locationName" => "RPC ACEH","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '1',"locationName" => "RPC ACEH","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                
                
                [ 'locationId' => '2',"locationName" => "RPC SUMATERA UTARA","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '2',"locationName" => "RPC SUMATERA UTARA","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '2',"locationName" => "RPC SUMATERA UTARA","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '2',"locationName" => "RPC SUMATERA UTARA","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
               
                [ 'locationId' => '3',"locationName" => "RPC SUMATERA BARAT","unitName"=> "KANDANG HAMSTER MURAH","status"=>1,"capacity"=>25,"amount"=>25,"notes"=>"Kandang Hamster mini cocok seagai penyimpanan hewan kecil hamster sebagai tempat tinggal ... terdapat joging welll atau alat olah raga untuk hamster berputar ","isDeleted"=>0,'created_at' =>now(),],               
                [ 'locationId' => '3',"locationName" => "RPC SUMATERA BARAT","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '3',"locationName" => "RPC SUMATERA BARAT","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "Universal World Pet Tuna Freshpack","status"=>1,"capacity"=>125,"amount"=>125,"notes"=>"Makanan Kucing Merek Universal World Pet","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "Makanan kucing kecil kucing kitten universal kitten 1,5 kg","status"=>1,"capacity"=>200,"amount"=>200,"notes"=>"mempercepat pertumbuhan kitten","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "OBAT PENUMBUH BULU KUCING VITAMIN BULU RONTOK PITAK BOTAK HAIRZ","status"=>1,"capacity"=>2000,"amount"=>200,"notes"=>"HAIRZ 30 ml penumbuh bulu kucing agar menjadi lebat, ","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '4',"locationName" => "RPC JAMBI","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                
             
                [ 'locationId' => '5',"locationName" => "RPC SUMATERA SELATAN","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '6',"locationName" => "RPC BENGKULU","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '7',"locationName" => "RPC LAMPUNG","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '8',"locationName" => "RPC KEPULAUAN BANGKA BELITUNG","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '9',"locationName" => "RPC KEPULAUAN RIAU","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
     
                [ 'locationId' => '10',"locationName" => "RPC DKI JAKARTA","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '11',"locationName" => "RPC JAWA BARAT","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '11',"locationName" => "RPC JAWA BARAT","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
              
                [ 'locationId' => '12',"locationName" => "RPC JAWA TENGAH","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '12',"locationName" => "RPC JAWA TENGAH","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '12',"locationName" => "RPC JAWA TENGAH","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '13',"locationName" => "RPC DI YOGYAKARTA","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
        
        
                [ 'locationId' => '14',"locationName" => "RPC JAWA TIMUR","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '14',"locationName" => "RPC JAWA TIMUR","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '15',"locationName" => "RPC BANTEN","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '16',"locationName" => "RPC BALI","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '16',"locationName" => "RPC BALI","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                
                
                [ 'locationId' => '17',"locationName" => "RPC NUSA TENGGARA BARAT","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '18',"locationName" => "RPC NUSA TENGGARA TIMUR","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>100,"amount"=>100,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '18',"locationName" => "RPC NUSA TENGGARA TIMUR","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '18',"locationName" => "RPC NUSA TENGGARA TIMUR","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '18',"locationName" => "RPC NUSA TENGGARA TIMUR","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '19',"locationName" => "RPC KALIMANTAN BARAT","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '19',"locationName" => "RPC KALIMANTAN BARAT","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                
                
                [ 'locationId' => '20',"locationName" => "RPC KALIMANTAN TENGAH","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
              
                [ 'locationId' => '21',"locationName" => "RPC KALIMANTAN SELATAN","unitName"=> "Mainan Kucing Anjing Kecoa Getar Bergerak","status"=>1,"capacity"=>24,"amount"=>24,"notes"=>"cat toys kecoa robot","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '21',"locationName" => "RPC KALIMANTAN SELATAN","unitName"=> "Mainan kucing dan anjing tikus remote jalan","status"=>1,"capacity"=>64,"amount"=>64,"notes"=>"memakai remote control","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '22',"locationName" => "RPC KALIMANTAN TIMUR","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '22',"locationName" => "RPC KALIMANTAN TIMUR","unitName"=> "Mainan Kucing Anjing Kecoa Getar Bergerak","status"=>1,"capacity"=>24,"amount"=>24,"notes"=>"cat toys kecoa robot","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '22',"locationName" => "RPC KALIMANTAN TIMUR","unitName"=> "Mainan kucing dan anjing tikus remote jalan","status"=>1,"capacity"=>64,"amount"=>64,"notes"=>"memakai remote control","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '23',"locationName" => "RPC KALIMANTAN UTARA","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '23',"locationName" => "RPC KALIMANTAN UTARA","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '23',"locationName" => "RPC KALIMANTAN UTARA","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                
                [ 'locationId' => '24',"locationName" => "RPC SULAWESI UTARA","unitName"=> "OBAT KUTU KUCING PENGHILANG PARASIT CAPLAX KATOBU","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Penghilang kutu","isDeleted"=>0,'created_at' =>now(),],               
                [ 'locationId' => '24',"locationName" => "RPC SULAWESI UTARA","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '24',"locationName" => "RPC SULAWESI UTARA","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '24',"locationName" => "RPC SULAWESI UTARA","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '25',"locationName" => "RPC SULAWESI TENGAH","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '26',"locationName" => "RPC SULAWESI SELATAN","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
    
                [ 'locationId' => '27',"locationName" => "RPC SULAWESI TENGGARA","unitName"=> "Mainan Kucing Anjing Kecoa Getar Bergerak","status"=>1,"capacity"=>24,"amount"=>24,"notes"=>"cat toys kecoa robot","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '27',"locationName" => "RPC SULAWESI TENGGARA","unitName"=> "Mainan kucing dan anjing tikus remote jalan","status"=>1,"capacity"=>64,"amount"=>64,"notes"=>"memakai remote control","isDeleted"=>0,'created_at' =>now(),],
     
                [ 'locationId' => '28',"locationName" => "RPC GORONTALO","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '28',"locationName" => "RPC GORONTALO","unitName"=> "Mainan Kucing Anjing Kecoa Getar Bergerak","status"=>1,"capacity"=>24,"amount"=>24,"notes"=>"cat toys kecoa robot","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '28',"locationName" => "RPC GORONTALO","unitName"=> "Mainan kucing dan anjing tikus remote jalan","status"=>1,"capacity"=>64,"amount"=>64,"notes"=>"memakai remote control","isDeleted"=>0,'created_at' =>now(),],
      
                [ 'locationId' => '29',"locationName" => "RPC SULAWESI BARAT","unitName"=> "Filter Air Aquarium dan AquaScape","status"=>1,"capacity"=>50,"amount"=>50,"notes"=>"Dan pembersi kotoran akuarium menjernihkan air akuarium","isDeleted"=>0,'created_at' =>now(),],
                [ 'locationId' => '29',"locationName" => "RPC SULAWESI BARAT","unitName"=> "PET CARGO KERANJANG RIO KUCING ANJING KELINCI MUSANG HEWAN","status"=>1,"capacity"=>20,"amount"=>20,"notes"=>"Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ","isDeleted"=>0,'created_at' =>now(),],
     
                [ 'locationId' => '30',"locationName" => "RPC MALUKU","unitName"=> "makan kucing MURAH bolt 1kg ikan","status"=>1,"capacity"=>500,"amount"=>500,"notes"=>"membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun","isDeleted"=>0,'created_at' =>now(),],
    
               ];		
    
                DB::table('facility_unit')->insert($facilityunit); 


    }
}
