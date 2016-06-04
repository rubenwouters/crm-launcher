<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Contact;
use Rubenwouters\CrmLauncher\Updates\UpdateAllCases;

class UpdateCases extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:updateCases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cases';


    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Updates\UpdateAllCases
     */
    protected $update;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(UpdateAllCases $update, Log $log)
    {
        parent::__construct();
        $this->update = $update;
        $this->log = $log;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (isFacebookLinked()) {
            $this->update->collectPrivateConversations();
            $this->update->collectPosts();
        }

        if (isTwitterLinked()) {
            $this->update->collectMentions();
            $this->update->collectDirectMessages();
        }

        $this->log->updateLog('fetching');
    }
}
