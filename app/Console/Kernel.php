<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->call(function(){
        //     info('call every minute');
        // })->everyMinute();

        //$schedule->command('App\Http\Controllers\StaffController@getAllHolidaysDate')->everyMinute(); //add by danny wahyudi 

       // $schedule->call('App\Http\Controllers\StaffController@getAllHolidaysDate')->everyMinute();
        $schedule->call('App\Http\Controllers\StaffController@getAllHolidaysDate')->weeklyOn(1, '8:00');
        // $schedule->call(function(){
        //         info('call every minute');
        //     })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
