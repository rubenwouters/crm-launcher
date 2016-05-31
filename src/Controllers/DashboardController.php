<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Auth;
use Socialite;
use Carbon\Carbon;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\Models\Answer;
use \Exception;

class DashboardController extends Controller
{
    /**
    * Shows dashboard when all required permissions are granted
    * @return view
    */
    public function index()
    {
        $filledOut = $this->isEnvFilledOut();

        if (! $filledOut) {
            return view('crm-launcher::dashboard.permissions')->with('filledOut', $filledOut);
        }

        if (! count(Configuration::all()) || (count(Configuration::all()) == 1 && Configuration::FbAccessToken() == "")) {
            return view('crm-launcher::dashboard.permissions')->with('filledOut', $filledOut);
        }

        if (($this->lastUpdate() > 900 || !$this->lastUpdate()) &&  $this->validTwitterSettings()) {
            $this->fetchFollowers();
            $this->fetchLikes();
            Log::updateLog('dashboard_update');
        }

        $newCases = CaseOverview::NewCases();
        $openCases = CaseOverview::OpenCases();
        $closedCases = CaseOverview::ClosedCases();
        $avgWaitTime = $this->getAvgWaitTime();
        $avgMessages = $this->getAvgMessages();
        $avgHelpers = $this->getAvgHelpers();
        $todaysMessages = $this->getTodaysMessages();
        $followers = Configuration::Followers();
        $likes = Configuration::Likes();

        return view('crm-launcher::dashboard.index')
            ->with('newCases', $newCases)
            ->with('openCases', $openCases)
            ->with('closedCases', $closedCases)
            ->with('avgWaitTime', $avgWaitTime)
            ->with('avgMessages', $avgMessages)
            ->with('avgHelpers', $avgHelpers)
            ->with('todaysMessages', $todaysMessages)
            ->with('followers', $followers)
            ->with('likes', $likes)
            ->with('filledOut', $filledOut);
    }

    /**
     * Ask permission on Facebook account.
     */
    public function askFbPermissions()
    {
        return Socialite::with('facebook')->scopes(['publish_pages', 'manage_pages', 'read_page_mailboxes'])->redirect();
    }

    /**
     * Handles logged in user and takes access token of the user.
     * @return view
     */
    public function fbCallback()
    {
        $fbUser = Socialite::with('facebook')->user();
        $token = $fbUser->token;
        $pageAccessToken = $this->getPageAccessToken($token);
        $this->insertFbToken($pageAccessToken);

        return redirect()->action('\Rubenwouters\CrmLauncher\Controllers\DashboardController@index');
    }

    /**
     * Checks if .ENV file is filled out
     * @return boolean
     */
    private function isEnvFilledOut()
    {
        if (! config('crm-launcher.twitter_credentials.twitter_consumer_key') ||
            ! config('crm-launcher.twitter_credentials.twitter_consumer_secret') ||
            ! config('crm-launcher.twitter_credentials.twitter_access_token') ||
            ! config('crm-launcher.twitter_credentials.twitter_access_token_secret') ||
            ! config('crm-launcher.facebook_credentials.facebook_app_id') ||
            ! config('crm-launcher.facebook_credentials.facebook_app_secret') ||
            ! config('crm-launcher.facebook_credentials.facebook_page_id')
        ) {
            return false;
        }

        return true;
    }

    private function validTwitterSettings()
    {
        try {
            $client = initTwitter();
            $verification = $client->get('account/verify_credentials.json');
            $verification = json_decode($verification->getBody(), true);

            if (Configuration::exists() && Configuration::first()->exists()) {
                $this->insertTwitterId($verification);
            }

            return true;

        } catch (\GuzzleHttp\Exception\ClientException $e) {

            if ($e->getCode() == 429) {
                getErrorMessage($e->getResponse()->getStatusCode());
            } else {
                getErrorMessage('bad_auth');
            }

            return false;
        }
    }

    /**
     * Uses user access token to become never-expiring page access token.
     * @param  string $userToken
     * @return string (page access token)
     */
    private function getPageAccessToken($userToken)
    {
        $fb = initFb();
        $response = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=access_token', $userToken);
        $response = json_decode($response->getBody());

        return $response->access_token;
    }

    /**
     * Insert Facebook access token
     * @param  string $token
     * @return void
     */
    private function insertFbToken($token)
    {
        if (count(Configuration::find(1)) < 1) {
            $config = new Configuration();
        } else {
            $config = Configuration::find(1);
        }

        $config->facebook_access_token = $token;
        $config->save();
    }

    /**
     * Get average wait time
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
        if(count($arTime) != 0){
            return round(array_sum($arTime)/count($arTime)/60);
        }

        return 0;
    }

    /**
     * Get average messages per case
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
     * Get average helpers per case
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
     * Get number answers sent today
     * @return integer
     */
    private function getTodaysMessages()
    {
        return count(Answer::TodaysAnswers());
    }

    /**
     * Get number of followers
     * @return integer
     */
    private function fetchFollowers()
    {
        try {
            $client = initTwitter();
            $pageId = Configuration::TwitterId();
            $lookup = $client->get('users/show/followers_count.json?user_id=' . $pageId);
            $lookup = json_decode($lookup->getBody(), true);
            $this->updateStats('twitter', $lookup['followers_count']);

            return $lookup['followers_count'];

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
            return back();
        }
    }

    /**
     * Get Facebook page likes
     * @return integer
     */
    private function fetchLikes()
    {
        try {
            $fb = initFb();
            $token = Configuration::FbAccessToken();
            $count = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=fan_count', $token);
            $count = json_decode($count->getBody(), true);

            $this->updateStats('facebook', $count['fan_count']);
            return $count['fan_count'];

        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Update config file with likes or followers
     * @param  string $type
     * @param  integer $nr
     * @return void
     */
    private function updateStats($type, $nr)
    {
        if ($type == 'twitter') {
            $config = Configuration::first();
            $config->twitter_followers = $nr;
            $config->save();
        } else if ($type == 'facebook') {
            $config = Configuration::first();
            $config->facebook_likes = $nr;
            $config->save();
        }
    }

    /**
     * Inserts Twitter id & screen name in configuration table
     * @param  collection $verification
     * @return void
     */
    private function insertTwitterId($verification)
    {
        $config = Configuration::first();
        $config->twitter_screen_name = $verification['screen_name'];
        $config->twitter_id = $verification['id_str'];
        $config->save();
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
