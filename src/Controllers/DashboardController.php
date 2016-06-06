<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Auth;
use Socialite;
use Carbon\Carbon;
use \Exception;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\ApiCalls\ValidateTwitter;

class DashboardController extends Controller
{
    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $log;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\ApiCalls\ValidateTwitter
     */
    protected $validateTwitter;


    /**
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\Case $case
     */
    public function __construct(Log $log, ValidateTwitter $validateTwitter)
    {
        $this->log = $log;
        $this->validateTwitter = $validateTwitter;
    }

    /**
    * Shows dashboard when all required permissions are granted
    * @return view
    */
    public function index()
    {
        if (! Configuration::exists() || ! Configuration::first()->valid_credentials) {

            $data = ['validTwitterSettings' => $this->validateTwitter->validTwitterSettings()];
            return view('crm-launcher::dashboard.facebook', $data);
        }

        $data = [
            'newCases' => CaseOverview::newCases(),
            'openCases' => CaseOverview::openCases(),
            'closedCases' => CaseOverview::closedCases(),
            'avgWaitTime' => $this->getAvgWaitTime(),
            'avgMessages' => $this->getAvgMessages(),
            'avgHelpers' => $this->getAvgHelpers(),
            'todaysMessages' => $this->getTodaysMessages(),
            'followers' => Configuration::first()->twitter_followers,
            'likes' => Configuration::first()->facebook_likes,
        ];

        return view('crm-launcher::dashboard.index', $data);
    }

    /**
     * Updates config record to a valid state after checks
     * @return view
     */
    public function launch()
    {
        if (! Configuration::exists()) {
            $config = new Configuration();
            $config->save();

            $this->validateTwitter->validTwitterSettings();
        } else {
            $config = Configuration::first();
        }

        $config->valid_credentials = 1;
        $config->save();

        return redirect()->action('\Rubenwouters\CrmLauncher\Controllers\DashboardController@index');
    }

    /**
     * Gets average wait time
     * @return integer
     */
    private function getAvgWaitTime()
    {
        $cases = CaseOverview::PendingCases();
        $arTime = [];

        foreach ($cases as $key => $case) {
            foreach ($case->messages as $id => $message) {
                if ($message->answers()->exists()) {

                    $postDate = new Carbon($message->post_date);
                    $answerDate = new Carbon($message->answers()->first()->post_date);
                    $waitTime = $answerDate->diffInSeconds($postDate);
                    array_push($arTime, $waitTime);

                }
            }
        }

        if (count($arTime) != 0) {
            return round(array_sum($arTime)/count($arTime)/60);
        }

        return 0;
    }

    /**
     * Gets average messages per case
     * @return integer
     */
    private function getAvgMessages()
    {
        $cases = CaseOverview::ClosedCases();
        $counter = 0;

        foreach ($cases as $key => $case) {
            foreach ($case->messages as $key => $message) {
                $counter += count($message->answers);
            }
        }

        if ($counter > 0) {

            return round($counter/count($cases), 1);
        }

        return 0;
    }

    /**
     * Gets average helpers per case
     * @return integer
     */
    private function getAvgHelpers()
    {
        $cases = CaseOverview::ClosedCases();
        $counter = 0;

        foreach ($cases as $key => $case) {
            $counter += count($case->users);
        }

        if ($counter > 0) {

            return round($counter/count($cases), 1);
        }

        return 0;
    }

    /**
     * Gets number answers sent today
     * @return integer
     */
    private function getTodaysMessages()
    {
        return count(Answer::TodaysAnswers());
    }

    private function lastUpdate()
    {
        if (Log::DashboardUpdate()->exists()) {

            $lastUpdate = Log::DashboardUpdate()->orderBy('id', 'DESC')->first()->created_at;
            $now = Carbon::now();
            $last = new Carbon($lastUpdate);

            return $now->diffInSeconds($last);
        }

        return false;
    }
}
