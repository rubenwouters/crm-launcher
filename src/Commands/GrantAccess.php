<?php

namespace Rubenwouters\CrmLauncher\Commands;

use Illuminate\Console\Command;
use App\User;

class GrantAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-launcher:grant {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Grant access to CRM views.";

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $email = $this->argument('email');

        if(User::where('email', $email)->exists()){
            $user = User::where('email', $email)->first();
            $state = $user->canViewCRM;
            $user->canViewCRM = !$state;
            $user->save();

            if (!$state) {
                $this->info('Permissions granted to: ' . $email);
            } else {
                $this->info('Permissions revoked for: ' . $email);
            }
        } else {
            $this->error($email . ' not found. Try again.');
        }
    }
}
