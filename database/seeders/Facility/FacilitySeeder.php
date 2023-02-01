<?php

namespace Database\Seeders\Facility;

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

            ['locationId' => '1', "introduction" => "Motivational pet shop", "description" => "full range of motivators and a range of different items to suit all budgets.", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '2', "introduction" => "A pet shop is a place where people can buy or trade things like pets.", "description" => "Pet shops can be found in most major cities.", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '3', "introduction" => "The first step to setting yourself up for success is finding something that you're passionate about", "description" => "Inspirational, motivational and motivational", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '4', "introduction" => "find a great pet for your family ", "description" => "Hanya satu tujuan, selamatkan hewanmu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '5', "introduction" => "This shop is for all your pet supplies. ", "description" => "Menerangi setiap kehidupan", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '6', "introduction" => "Motivation is a funny thing. It can start from a small spark and just keep growing", "description" => "Kami adalah aktivis kesehatan hewan", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '7', "introduction" => "This shop is for all your pet supplies. ", "description" => "Pusat spesialisasi baru", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '8', "introduction" => "This is a guide to encourage and motivate the pet shop person to do their job well.", "description" => "Memberi harapan untuk hidup lebih lama", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '9', "introduction" => "This shop is for all your pet supplies. ", "description" => "Peduli itu penting", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '10', "introduction" => "A pet shop is a place where people can purchase and sell animals or items relating to them.", "description" => "Saatnya mencoba lebih baik", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '11', "introduction" => "This is a guide to encourage and motivate the pet shop person to do their job well.", "description" => "Membentuk perawatan baru", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '12', "introduction" => "A pet shop is a place where people can purchase and sell animals or items relating to them.", "description" => "Kami mengkhususkan diri dalam satu hal, hewan.", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '13', "introduction" => "menyediakan berbagai kebutuhan hewan kesayangan anda", "description" => "Menyelaraskan janji dengan kesehatan", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '14', "introduction" => "Menjual berbagai macam makanan dan aksesoris hewan", "description" => "Tim spesialis", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '15', "introduction" => "Berbagi cinta dengan kepedulian kita", "description" => "Hewan dulu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '16', "introduction" => "Hati-hati itu berharga", "description" => "Peduli adalah strategi kami", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '17', "introduction" => "Perspektif baru tentang perawatan hewan", "description" => "Untuk hidup sesuai dengan kepercayaan", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '18', "introduction" => "Biarkan hewan Anda terbang", "description" => "Merawat orang yang Anda cintai", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '19', "introduction" => "Dokter keluarga untuk anggota keluarga Anda", "description" => "Kami peduli apa yang ada di dalamnya", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '20', "introduction" => "Perawatan terbaik untuk teman berbulu Anda", "description" => "Perawatan hewan terbaik", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '21', "introduction" => "Teman berkaki empat membutuhkan perawatan terbaik ", "description" => "Ide untuk merawat hewan Anda", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '22', "introduction" => "Menyembuhkan tangan untuk hewan peliharaan Anda", "description" => "Bersama-sama lebih baik", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '23', "introduction" => "Hewan peliharaan Anda adalah teman terbaik kami juga", "description" => "Ikuti kata hatimu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '24', "introduction" => "Dua tangan membantu untuk empat kaki", "description" => "Mitra terbaik Anda", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '25', "introduction" => "Hewan peliharaan Anda lebih memilih kami ", "description" => "Jaga kesehatan hewan Anda", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '26', "introduction" => "Semua yang dibutuhkan hewan peliharaan Anda", "description" => "Semua kemungkinan kesehatan", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '27', "introduction" => "Hewan adalah hewan peliharaan kita", "description" => "Sepanjang hari, perawatan harian", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '28', "introduction" => "Nyawa hewan sangat berharga,", "description" => "Hewan peliharaan yang sehat, hewan peliharaan yang bahagia", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '29', "introduction" => "Lebih berhati-hati, kurangi rasa takut", "description" => "Kami memperlakukan mereka seolah-olah mereka milik kami", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '30', "introduction" => "Banyak hewan, banyak hati", "description" => "Cinta penuh kasih sayang", "isDeleted" => 0, 'created_at' => now(),],

        ];

        DB::table('facility')->insert($facility);



        $facilityunit = [

            ['locationId' => '1', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '1', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '1', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '2', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '2', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '2', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '2', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '3', "unitName" => "KANDANG HAMSTER MURAH", "status" => 1, "capacity" => 25, "amount" => 25, "notes" => "Kandang Hamster mini cocok seagai penyimpanan hewan kecil hamster sebagai tempat tinggal ... terdapat joging welll atau alat olah raga untuk hamster berputar ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '3', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '3', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '4', "unitName" => "Universal World Pet Tuna", "status" => 1, "capacity" => 125, "amount" => 125, "notes" => "Makanan Kucing Merek Universal World Pet", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '4', "unitName" => "Makanan kitten universal", "status" => 1, "capacity" => 200, "amount" => 200, "notes" => "mempercepat pertumbuhan kitten", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '4', "unitName" => "PENUMBUH BULU VITAMIN", "status" => 1, "capacity" => 2000, "amount" => 200, "notes" => "HAIRZ 30 ml penumbuh bulu kucing agar menjadi lebat, ", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '4', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '4', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '4', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '4', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '5', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '6', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '7', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '8', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '9', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '10', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '11', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '11', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '12', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '12', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '12', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '13', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '14', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '14', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '15', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '16', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '16', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '17', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '18', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 100, "amount" => 100, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '18', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '18', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '18', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '19', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '19', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],


            ['locationId' => '20', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '21', "unitName" => "Mainan Kucing Kecoa Getar", "status" => 1, "capacity" => 24, "amount" => 24, "notes" => "cat toys kecoa robot", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '21', "unitName" => "Mainan kucing remot jalan", "status" => 1, "capacity" => 64, "amount" => 64, "notes" => "memakai remote control", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '22', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '22', "unitName" => "Mainan Kucing Kecoa Getar", "status" => 1, "capacity" => 24, "amount" => 24, "notes" => "cat toys kecoa robot", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '22', "unitName" => "Mainan kucing remot jalan", "status" => 1, "capacity" => 64, "amount" => 64, "notes" => "memakai remote control", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '23', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '23', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '23', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '24', "unitName" => "OBAT KUTU KUCING CAPLAX", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Penghilang kutu", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '24', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '24', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '24', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '25', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '26', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '27', "unitName" => "Mainan Kucing Kecoa Getar", "status" => 1, "capacity" => 24, "amount" => 24, "notes" => "cat toys kecoa robot", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '27', "unitName" => "Mainan kucing remot jalan", "status" => 1, "capacity" => 64, "amount" => 64, "notes" => "memakai remote control", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '28', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '28', "unitName" => "Mainan Kucing Kecoa Getar", "status" => 1, "capacity" => 24, "amount" => 24, "notes" => "cat toys kecoa robot", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '28', "unitName" => "Mainan kucing remot jalan", "status" => 1, "capacity" => 64, "amount" => 64, "notes" => "memakai remote control", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '29', "unitName" => "Filter Air Aquarium", "status" => 1, "capacity" => 50, "amount" => 50, "notes" => "Dan pembersi kotoran akuarium menjernihkan air akuarium", "isDeleted" => 0, 'created_at' => now(),],
            ['locationId' => '29', "unitName" => "PET CARGO KERANJANG", "status" => 1, "capacity" => 20, "amount" => 20, "notes" => "Keranjang Rio Kecil dapat digunakan untuk berbagai macam keperluan anda sebagai keranjang belanja, keranjang piknik, untuk membawa hewan peliharaan anda ", "isDeleted" => 0, 'created_at' => now(),],

            ['locationId' => '30', "unitName" => "makan kucing bolt", "status" => 1, "capacity" => 500, "amount" => 500, "notes" => "membuat kulit sehat dan berkilau mempertajam pengelihatan membantu kesehatan gigi meningkatkan sistem imunitas untuk kucing diatas 1tahun", "isDeleted" => 0, 'created_at' => now(),],

        ];

        DB::table('facility_unit')->insert($facilityunit);
    }
}
