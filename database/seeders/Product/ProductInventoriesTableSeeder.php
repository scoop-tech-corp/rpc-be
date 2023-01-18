<?php

namespace Database\Seeders\Product;

use Illuminate\Database\Seeder;

class ProductInventoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('productInventories')->delete();
        
        \DB::table('productInventories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'requirementName' => 'Penambahan Barang',
                'locationId' => 7,
                'totalItem' => 3,
                'isApprovalAdmin' => 0,
                'isApprovalOffice' => 1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:20:05',
                'updated_at' => '2023-01-15 23:20:05',
            ),
            1 => 
            array (
                'id' => 2,
                'requirementName' => 'Penambahan Barang',
                'locationId' => 7,
                'totalItem' => 3,
                'isApprovalAdmin' => 0,
                'isApprovalOffice' => 1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-15 23:20:15',
                'updated_at' => '2023-01-15 23:20:15',
            ),
            2 => 
            array (
                'id' => 3,
                'requirementName' => 'Penambahan Barang 3',
                'locationId' => 7,
                'totalItem' => 3,
                'isApprovalAdmin' => 0,
                'isApprovalOffice' => 1,
                'isDeleted' => 0,
                'userId' => 3,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-16 01:52:00',
                'updated_at' => '2023-01-16 01:52:00',
            ),
            3 => 
            array (
                'id' => 9,
                'requirementName' => 'Penambahan Barang',
                'locationId' => 7,
                'totalItem' => 3,
                'isApprovalAdmin' => 0,
                'isApprovalOffice' => 1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:00:29',
                'updated_at' => '2023-01-19 01:00:29',
            ),
            4 => 
            array (
                'id' => 10,
                'requirementName' => 'Penambahan Barang ke sekian',
                'locationId' => 7,
                'totalItem' => 3,
                'isApprovalAdmin' => 0,
                'isApprovalOffice' => 1,
                'isDeleted' => 0,
                'userId' => 1,
                'userUpdateId' => NULL,
                'deletedBy' => NULL,
                'deletedAt' => NULL,
                'created_at' => '2023-01-19 01:00:41',
                'updated_at' => '2023-01-19 01:00:41',
            ),
        ));
        
        
    }
}