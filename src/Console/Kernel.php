<?php

namespace Rubenwouters\CrmLauncher\Console;

use App\Console\Kernel as ConsoleKernel;
use Illuminate\Console\Scheduling\Schedule;
use Rubenwouters\CrmLauncher\Models\Message;

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
        parent::schedule($schedule);

        $schedule->command('crm-launcher:updateCases')->everyMinute()->when(function () {
            return Message::exists();
        });

        $schedule->command('crm-launcher:updateDashboardStats')->everyFiveMinutes();
        $schedule->command('crm-launcher:updatePublishmentStats')->everyFiveMinutes();
    }
}
