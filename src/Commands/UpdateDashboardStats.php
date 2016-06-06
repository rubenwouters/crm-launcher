<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Updates\UpdateStatistics;

class UpdateDashboardStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:updateDashboardStats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update dashboard statistics.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Log $log,
        UpdateStatistics $stats
    ) {
        parent::__construct();
        $this->log = $log;
        $this->stats = $stats;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (isTwitterLinked()) {
            $this->stats->updateTwitterDashboardStats();
        }

        if (isFacebookLinked()) {
            $this->stats->updateFacebookDashboardStats();
        }

        $this->log->updateLog('stats');
    }
}
