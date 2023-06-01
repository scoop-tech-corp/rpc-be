<?php

namespace Database\Seeders;

use Database\Seeders\Product\ProductSellsTableSeeder;
use Database\Seeders\Product\ProductBrandsTableSeeder;
use Database\Seeders\Product\ProductBundleDetailsTableSeeder;
use Database\Seeders\Product\ProductBundleLogsTableSeeder;
use Database\Seeders\Product\ProductBundlesTableSeeder;
use Database\Seeders\Product\ProductCategoriesTableSeeder;
use Database\Seeders\Product\ProductClinicCategoriesTableSeeder;
use Database\Seeders\Product\ProductClinicCustomerGroupsTableSeeder;
use Database\Seeders\Product\ProductClinicDosagesTableSeeder;
use Database\Seeders\Product\ProductClinicImagesTableSeeder;
use Database\Seeders\Product\ProductClinicLocationsTableSeeder;
use Database\Seeders\Product\ProductClinicPriceLocationsTableSeeder;
use Database\Seeders\Product\ProductClinicQuantitiesTableSeeder;
use Database\Seeders\Product\ProductClinicRemindersTableSeeder;
use Database\Seeders\Product\ProductClinicsTableSeeder;
use Database\Seeders\Product\ProductInventoriesTableSeeder;
use Database\Seeders\Product\ProductInventoryListImagesTableSeeder;
use Database\Seeders\Product\ProductInventoryListsTableSeeder;
use Database\Seeders\Product\ProductSellCategoriesTableSeeder;
use Database\Seeders\Product\ProductSellCustomerGroupsTableSeeder;
use Database\Seeders\Product\ProductSellImagesTableSeeder;
use Database\Seeders\Product\ProductSellLocationsTableSeeder;
use Database\Seeders\Product\ProductSellPriceLocationsTableSeeder;
use Database\Seeders\Product\ProductSellQuantitiesTableSeeder;
use Database\Seeders\Product\ProductSellRemindersTableSeeder;
use Database\Seeders\Product\ProductSuppliersTableSeeder;
use Database\Seeders\Customer\CustomerGroupsTableSeeder;
use Database\Seeders\Product\UsagesTableSeeder;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsMaster extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ProductSellsTableSeeder::class);
        $this->call(ProductBrandsTableSeeder::class);
        $this->call(ProductBundleDetailsTableSeeder::class);
        $this->call(ProductBundleLogsTableSeeder::class);
        $this->call(ProductBundlesTableSeeder::class);
        $this->call(ProductCategoriesTableSeeder::class);
        $this->call(ProductClinicCategoriesTableSeeder::class);
        $this->call(ProductClinicCustomerGroupsTableSeeder::class);
        $this->call(ProductClinicDosagesTableSeeder::class);
        $this->call(ProductClinicImagesTableSeeder::class);
        $this->call(ProductClinicLocationsTableSeeder::class);
        $this->call(ProductClinicPriceLocationsTableSeeder::class);
        $this->call(ProductClinicQuantitiesTableSeeder::class);
        $this->call(ProductClinicRemindersTableSeeder::class);
        $this->call(ProductClinicsTableSeeder::class);
        $this->call(ProductInventoriesTableSeeder::class);
        $this->call(ProductInventoryListImagesTableSeeder::class);
        $this->call(ProductInventoryListsTableSeeder::class);
        $this->call(ProductSellCategoriesTableSeeder::class);
        $this->call(ProductSellCustomerGroupsTableSeeder::class);
        $this->call(ProductSellImagesTableSeeder::class);
        $this->call(ProductSellLocationsTableSeeder::class);
        $this->call(ProductSellPriceLocationsTableSeeder::class);
        $this->call(ProductSellQuantitiesTableSeeder::class);
        $this->call(ProductSellRemindersTableSeeder::class);
        $this->call(ProductSuppliersTableSeeder::class);
        $this->call(CustomerGroupsTableSeeder::class);
        $this->call(UsagesTableSeeder::class);
    }
}
