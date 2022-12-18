<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoCorn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:corn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info("Cron job Berhasil di jalankan " . date('Y-m-d H:i:s'));
    }
}
