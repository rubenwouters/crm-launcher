<?php

namespace Rubenwouters\CrmLauncher\Updates;

use Carbon\Carbon;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\Configuration;
use App\User;
use Mail;

class CheckActivity {

    /**
     * @var Rubenwouters\CrmLauncher\Models\Message
     */
    protected $message;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Message
     */
    protected $config;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Message $message
     * @param Rubenwouters\CrmLauncher\Models\Configuration $config
     */
    public function __construct(
        Message $message,
        Configuration $config
    ) {
        $this->message = $message;
        $this->config = $config;
    }

    /**
     * Checks if number of todays messages are greater than max
     * @return boolean [description]
     */
    public function isAboveMax()
    {
        if ($this->message->where('post_date', '>', Carbon::today())->count() > config('crm-launcher.notification.crm_notify_from_messages')) {
            return true;
        }

        return false;
    }

    /**
     * Send mail to notify above average social media activity
     *
     * @return void
     */
    public function sendNotifications()
    {
        if (!$this->config->first()->notified_today) {
            $team = User::where('canViewCRM', '1')->get();

            foreach ($team as $key => $user) {
                Mail::send('crm-launcher::emails.activity', ['name' => $user->name], function ($m) use ($user) {
                    $m->from('noreply@crmlauncher.be', 'CRM Launcher');
                    $m->to($user->email, $user->name)->subject('Above average activity on you social media');
                });
            }

            $this->toggleSentNotification();
        }
    }

    /**
     * Remember that a notification is sent today
     * @return void
     */
    private function toggleSentNotification()
    {
        $config = $this->config->first();
        $config->notified_today = 1;
        $config->save();
    }
}
