<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use Rubenwouters\CrmLauncher\Updates\CheckActivity;

class CheckActivityFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors activity on your social media accounts.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CheckActivity $activity)
    {
        parent::__construct();
        $this->activity = $activity;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $isAboveMax = $this->activity->isAboveMax();

        if ($isAboveMax) {
            $this->activity->sendNotifications();
        }
    }
}
