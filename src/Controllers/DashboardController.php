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

class DashboardController extends Controller
{
    /**
    * Shows dashboard when all required permissions are granted
    * @return view
    */
    public function index()
    {
        if (! Configuration::exists() || ! Configuration::first()->valid_credentials) {
            return view('crm-launcher::dashboard.facebook');
        }

        $config = Configuration::first();

        if ($config->valid_credentials && ($this->lastUpdate() > 900 || !$this->lastUpdate())) {

            if (isFacebookLinked()) {
                $this->fetchLikes();
            }

            if (isTwitterLinked()) {
                $this->fetchFollowers();
            }

            Log::updateLog('dashboard_update');
        }

        $newCases = CaseOverview::newCases();
        $openCases = CaseOverview::openCases();
        $closedCases = CaseOverview::closedCases();
        $avgWaitTime = $this->getAvgWaitTime();
        $avgMessages = $this->getAvgMessages();
        $avgHelpers = $this->getAvgHelpers();
        $todaysMessages = $this->getTodaysMessages();
        $followers = Configuration::first()->twitter_followers;
        $likes = Configuration::first()->facebook_likes;

        return view('crm-launcher::dashboard.index')
            ->with('newCases', $newCases)
            ->with('openCases', $openCases)
            ->with('closedCases', $closedCases)
            ->with('avgWaitTime', $avgWaitTime)
            ->with('avgMessages', $avgMessages)
            ->with('avgHelpers', $avgHelpers)
            ->with('todaysMessages', $todaysMessages)
            ->with('followers', $followers)
            ->with('likes', $likes);
    }

    /**
     * Ask permission on Facebook account.
     * @return view
     */
    public function askFbPermissions()
    {
        return Socialite::with('facebook')->scopes(['publish_pages', 'manage_pages', 'read_page_mailboxes'])->redirect();
    }

    /**
     * Handles redirect by Facebook after login. Inserts Facebook page Access token
     * @return view
     */
    public function fbCallback()
    {
        try {
            $fbUser = Socialite::with('facebook')->user();
            $token = $fbUser->token;
            $pageAccessToken = $this->getPageAccessToken($token);

            if ($pageAccessToken) {
                $this->insertFbToken($pageAccessToken);
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
        }

        return redirect('/crm/dashboard');
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

            validTwitterSettings();
        } else {
            $config = Configuration::first();
        }

        $config->valid_credentials = 1;
        $config->save();

        return redirect()->action('\Rubenwouters\CrmLauncher\Controllers\DashboardController@index');
    }

    /**
     * Uses user access token to become never-expiring page access token.
     * @param  string $userToken
     * @return string (page access token)
     */
    private function getPageAccessToken($userToken)
    {
        $fb = initFb();

        try {
            $response = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=access_token', $userToken);
            $response = json_decode($response->getBody());
        } catch (Exception $e) {
            getErrorMessage('permission');
            return false;
        }

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

        $config->linked_facebook = 1;
        $config->facebook_access_token = $token;
        $config->save();
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
