<?php

namespace Database\Seeders\Region;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Imports\RegionImport;
use App\Imports\KabupatenImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use File;

class regionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $icons = database_path('seeders/FileMapping');
        $files = File::allFiles($icons);

        foreach ($files as $file) {

            if (str_contains($file, "Kabupaten")) {

                Excel::import(new KabupatenImport, $file);
                
            } else {

                Excel::import(new RegionImport, $file);
            }
        }
    }
}
