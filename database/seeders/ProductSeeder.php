<?php

namespace Database\Seeders;

use App\Models\ProductSell;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProductSell::unguard();
        $prod = public_path('assets/sql/productSells.sql');
        DB::unprepared(file_get_contents($prod)); 
        // DB::table('productSells')->insert([

        //     'fullName' => 'Whiskas 2kg',
        //     'simpleName' => null,
        //     'sku' => null,
        //     'productBrandId' => '1',
        //     'productSupplierId' => '2',
        //     'status' => '1',
        //     'expiredDate' => '2022-10-20',

        //     'pricingStatus' => 'Quantities',
        //     'costPrice' => '15000',
        //     'marketPrice' => '35000',
        //     'price' => '35000',

        //     'isShipped' => '1',
        //     'weight' => '0',
        //     'length' => '0',
        //     'width' => '0',
        //     'height' => '0',

        //     'introduction' => 'dos soloet es amito imago eprno kardo',
        //     'description' => 'dos soloet es amito imago eprno kardo',

        //     'isDeleted' => '0',
        //     'userId' => '2',
        //     'userUpdateId' => null,
        //     'deletedBy' => null,
        //     'deletedAt' => null,
        //     'created_at' => Carbon::now(),
        //     'updated_at' => null,
        // ]);

        // DB::table('productSells')->insert([

        //     'fullName' => 'RC 5kg',
        //     'simpleName' => null,
        //     'sku' => null,
        //     'productBrandId' => '1',
        //     'productSupplierId' => '2',
        //     'status' => '1',
        //     'expiredDate' => '2022-10-20',

        //     'pricingStatus' => 'Basic',
        //     'costPrice' => '65000',
        //     'marketPrice' => '195000',
        //     'price' => '145000',

        //     'isShipped' => '1',
        //     'weight' => '0',
        //     'length' => '0',
        //     'width' => '0',
        //     'height' => '0',

        //     'introduction' => 'dos soloet es amito imago eprno kardo',
        //     'description' => 'dos soloet es amito imago eprno kardo',

        //     'isDeleted' => '0',
        //     'userId' => '2',
        //     'userUpdateId' => null,
        //     'deletedBy' => null,
        //     'deletedAt' => null,
        //     'created_at' => Carbon::now(),
        //     'updated_at' => null,
        // ]);

        // DB::table('productSells')->insert([

        //     'fullName' => 'RC 5kg',
        //     'simpleName' => null,
        //     'sku' => null,
        //     'productBrandId' => '1',
        //     'productSupplierId' => '2',
        //     'status' => '1',
        //     'expiredDate' => '2022-10-20',

        //     'pricingStatus' => 'CustomerGroups',
        //     'costPrice' => '65000',
        //     'marketPrice' => '195000',
        //     'price' => '145000',

        //     'isShipped' => '1',
        //     'weight' => '0',
        //     'length' => '0',
        //     'width' => '0',
        //     'height' => '0',

        //     'introduction' => 'dos soloet es amito imago eprno kardo',
        //     'description' => 'dos soloet es amito imago eprno kardo',

        //     'isDeleted' => '0',
        //     'userId' => '2',
        //     'userUpdateId' => null,
        //     'deletedBy' => null,
        //     'deletedAt' => null,
        //     'created_at' => Carbon::now(),
        //     'updated_at' => null,
        // ]);
    }
}
