<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Updates\UpdateStatistics;

class UpdatePublishmentStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:updatePublishmentStats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates Twitter followers & Facebook page likes';

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Update\UpdateStatistics
     */
    protected $stats;

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
        if (isFacebookLinked()) {
            $this->stats->updateFbStats();
        }

        if (isTwitterLinked()) {
            $this->stats->updateTwitterStats();
        }

        $this->log->updateLog('stats');
    }
}
