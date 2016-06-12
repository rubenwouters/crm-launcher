<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\ApiCalls\ValidateTwitter;

class DashboardController extends Controller
{
    /**
     * @var Rubenwouters\CrmLauncher\Models\Log
     */
    protected $log;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Answer
     */
    protected $answer;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Configuration
     */
    protected $config;

    /**
     * @var Rubenwouters\CrmLauncher\Models\CaseOverview
     */
    protected $case;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\ValidateTwitter
     */
    protected $validateTwitter;


    /**
     * @param Rubenwouters\CrmLauncher\Models\Log $log
     * @param Rubenwouters\CrmLauncher\Models\Answer  $answer
     * @param Rubenwouters\CrmLauncher\Models\Configuration $config
     * @param Rubenwouters\CrmLauncher\Models\Case $case
     * @param ValidateTwitter $validateTwitter
     */
    public function __construct(
        Log $log,
        Answer $answer,
        Configuration $config,
        CaseOverview $case,
        ValidateTwitter $validateTwitter
    ) {
        $this->log = $log;
        $this->answer = $answer;
        $this->case = $case;
        $this->config = $config;
        $this->validateTwitter = $validateTwitter;
    }

    /**
     * Shows dashboard when all required permissions are granted
     *
     * @return view
     */
    public function index()
    {
        if (!$this->config->exists() || !$this->config->first()->valid_credentials) {

            $data = ['validTwitterSettings' => $this->validateTwitter->validTwitterSettings()];
            return view('crm-launcher::dashboard.facebook', $data);
        }

        $data = [
            'newCases' => $this->case->newCases(),
            'openCases' => $this->case->openCases(),
            'closedCases' => $this->case->closedCases(),
            'avgWaitTime' => $this->getAvgWaitTime(),
            'avgMessages' => $this->getAvgMessages(),
            'avgHelpers' => $this->getAvgHelpers(),
            'todaysMessages' => $this->getTodaysMessages(),
            'followers' => $this->config->first()->twitter_followers,
            'likes' => $this->config->first()->facebook_likes,
        ];

        return view('crm-launcher::dashboard.index', $data);
    }

    /**
     * Updates config record to a valid state after checks
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function launch()
    {
        if (!$this->config->exists()) {
            $config = new Configuration();
            $config->save();

            $this->validateTwitter->validTwitterSettings();
        } else {
            $config = $this->config->first();
        }

        $config->valid_credentials = 1;
        $config->save();

        return redirect()->action('\Rubenwouters\CrmLauncher\Controllers\DashboardController@index');
    }

    /**
     * Gets average wait time
     *
     * @return integer
     */
    private function getAvgWaitTime()
    {
        $cases = $this->case->PendingCases();
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
     *
     * @return integer
     */
    private function getAvgMessages()
    {
        $cases = $this->case->ClosedCases();
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
     *
     * @return integer
     */
    private function getAvgHelpers()
    {
        $cases = $this->case->ClosedCases();
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
     *
     * @return integer
     */
    private function getTodaysMessages()
    {
        return count($this->answer->TodaysAnswers());
    }
}
