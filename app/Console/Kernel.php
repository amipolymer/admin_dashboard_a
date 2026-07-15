<?php
// file path : app\console\kernel.php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    // Optional: manual command registration
    protected $commands = [
        \App\Console\Commands\UpdateSiteStatusDaily::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('site:update-status-daily')->dailyAt('03:02')->timezone('Asia/Kolkata');
        $schedule->command('site:update-status-daily')->everyMinute()->timezone('Asia/Kolkata');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
