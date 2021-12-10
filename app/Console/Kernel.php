<?php

namespace App\Console;

use App\Jobs\Inplay;
use App\Jobs\InplayFifa22;
use App\Jobs\UpdateMatches;
use App\Jobs\UpdateMatches10Min;
use App\Jobs\UpdateMatches12Min;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

	    $schedule->job(new Inplay)->everyMinute();
	    // $schedule->job(new InplayFifa22)->everyMinute();
	    // $schedule->call(new UpdateMatches)->dailyAt('01:00');
	    // $schedule->call(new UpdateMatches10Min)->dailyAt('01:30');
	    // $schedule->call(new UpdateMatches12Min)->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
