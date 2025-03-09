<?php

namespace App\Console\Commands;

use App\Models\StaffAbsents;
use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Carbon;

class UpdateStaffHomeAbsent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'absent:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengupdate Kepulangan Staff secara otomatis';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DB::table('staffAbsents')
            ->whereDate('created_at', Carbon::today())
            ->where('statusPresent', '=', 1)
            ->update(
                [
                    'statusHome' => 5,
                    'homeTime' => Carbon::now(),
                    'duration' => DB::raw("TIME_FORMAT(SEC_TO_TIME(TIMESTAMPDIFF(SECOND, presentTime, NOW())), '%H:%i:%s')")
                ]
            );

        return Command::SUCCESS;
    }
}
