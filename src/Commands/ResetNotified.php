<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use Rubenwouters\CrmLauncher\Models\Configuration;

class ResetNotified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:resetNotified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset notified_today value to recieve another e-mail.';

    /**
     * @var Rubenwouters\CrmLauncher\Models\Configuration
     */
    protected $config;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Configuration $config)
    {
        parent::__construct();
        $this->config = $config;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = $this->config->first();
        $config->notified_today = 0;
        $config->save();
    }
}
