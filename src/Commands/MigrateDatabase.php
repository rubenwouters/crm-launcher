<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;

class MigrateDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Run crm-launcher's migrations.";

    /**
     * @var array
     */
    protected $paths;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->paths = [
            'packages/rubenwouters/crm-launcher/database/Migrations/',
            'vendor/rubenwouters/crm-launcher/database/Migrations',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->paths as $path) {
            $this->call('migrate', ['--path' => $path]);
        }
    }
}
