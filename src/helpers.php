<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Rubenwouters\CrmLauncher\Models\Reaction;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Carbon\Carbon;

if (! function_exists('initFb'))
{
    /**
     * Initializes Facebook connection
     */
    function initFb()
    {
        $fb = new \Facebook\Facebook([
            'app_id' => config('crm-launcher.facebook_credentials.facebook_app_id'),
            'app_secret' => config('crm-launcher.facebook_credentials.facebook_app_secret'),
            'default_graph_version' => 'v2.6',
        ]);

        return $fb;
    }

    /**
     * Initializes Twitter connection
     */
    function initTwitter()
    {
        $stack = HandlerStack::create();

        $oauth = new Oauth1([
            'consumer_key' => config('crm-launcher.twitter_credentials.twitter_consumer_key'),
            'consumer_secret' => config('crm-launcher.twitter_credentials.twitter_consumer_secret'),
            'token' => config('crm-launcher.twitter_credentials.twitter_access_token'),
            'token_secret' => config('crm-launcher.twitter_credentials.twitter_access_token_secret')
        ]);

        $stack->push($oauth);

        $client = new Client([
            'base_uri' => 'https://api.twitter.com/1.1/',
            'handler' => $stack,
            'auth' => 'oauth'
        ]);

        return $client;
    }

    /**
     * Check if configurations are set
     * @return boolean
     */
    function hasFbPermissions()
    {
        return Configuration::exists() && Configuration::first()->facebook_access_token;
    }

    function validTwitterSettings()
    {
        if (Configuration::exists() && Configuration::first()->linked_twitter) {
            return true;
        }

        try {
            $client = initTwitter();
            $verification = $client->get('account/verify_credentials.json');
            $verification = json_decode($verification->getBody(), true);

            if (Configuration::exists() && Configuration::first()->exists()) {
                Configuration::insertTwitterId($verification);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            if ($e->getCode() == 429) {
                getErrorMessage($e->getResponse()->getStatusCode());
            }
            return false;
        }
        return true;
    }

    function isConfigured()
    {
        if (Configuration::exists() && Configuration::first()->valid_credentials) {
            return true;
        }

        return false;
    }

    function isTwitterLinked()
    {
        if (Configuration::exists()) {
            return Configuration::first()->linked_twitter;
        }

        return false;
    }

    function isFacebookLinked()
    {
        if (Configuration::exists()) {
            return Configuration::first()->linked_facebook;
        }

        return false;
    }

    /**
     * Checks if .ENV file is filled out with Twitter credentials
     * @return boolean
     */
    function isTwitterEnvFilledOut()
    {
        if (! config('crm-launcher.twitter_credentials.twitter_consumer_key') ||
            ! config('crm-launcher.twitter_credentials.twitter_consumer_secret') ||
            ! config('crm-launcher.twitter_credentials.twitter_access_token') ||
            ! config('crm-launcher.twitter_credentials.twitter_access_token_secret')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Checks if .ENV file is filled out with FB credentials
     * @return boolean
     */
    function isFbEnvFilledOut()
    {
        if (! config('crm-launcher.facebook_credentials.facebook_app_id') ||
            ! config('crm-launcher.facebook_credentials.facebook_app_secret') ||
            ! config('crm-launcher.facebook_credentials.facebook_page_id')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Fetch all mentions
     * @param  string $type
     * @return array
     */
    function fetchMentions($type)
    {
        $latestMentionId = LatestMentionId($type);

        try {
            $client = initTwitter();

            if ($latestMentionId) {
                $mentions_response = $client->get('statuses/mentions_timeline.json?since_id=' . $latestMentionId);
            } else {
                $mentions_response = $client->get('statuses/mentions_timeline.json?count=1');
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
            return back();
        }

        return json_decode($mentions_response->getBody(), true);
    }

    /**
     * Fetch comments on post
     * @return array
     */
    function fetchComments($newest, $message)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();

        try {
            if ($newest) {
                $comments = $fb->get('/' . $message->fb_post_id . '/comments?fields=from,message,created_time,count,comments,attachment', $token);
                return json_decode($comments->getBody());
            }
        } catch (Exception $e) {
            if ($e->getCode() != 100) {
                getErrorMessage($e->getCode());
                return back();
            }
        }
    }

    function fetchInnerComments($newest, $postId)
    {
        $token = Configuration::FbAccessToken();
        $fb = initFb();

        try {
            if ($newest) {
                $comments = $fb->get('/' . $postId . '/comments?fields=from,message,created_time,count,comments,attachment', $token);
                return json_decode($comments->getBody());
            }
        } catch (Exception $e) {
            if ($e->getCode() != 100) {
                getErrorMessage($e->getCode());
                return back();
            }
        }
    }

    /**
     * Gets latest mention id (Twitter).
     * @return id
     */
    function LatestMentionId($type)
    {
        if ($type == 'message' && Message::where('tweet_id', '!=', '')->exists()) {
            $tweetIdMessage = Message::latestMentionId();

            return $tweetIdMessage;
        } else if ($type == 'reaction' && Reaction::where('tweet_id', '!=', '')->exists()) {
            $tweetIdReaction = Reaction::latestMentionId();

            return $tweetIdReaction;
        }
        return false;
    }

    /**
     * Get latest comment date (Facebook)
     * @return datetime
     */
    function latestCommentDate()
    {
        if (Message::where('fb_reply_id', '!=', '')->exists() && Reaction::where('fb_post_id', '!=', '')->exists()) {
            $messageDate =  Message::where('fb_reply_id', '!=', '')
                ->orderBy('fb_post_id', 'ASC')
                ->first()->post_date;

            $reactionDate =  Reaction::where('fb_post_id', '!=', '')
                ->orderBy('fb_post_id', 'DESC')
                ->first()->post_date;

            return max($messageDate, $reactionDate);

        } else if (Message::where('fb_reply_id', '!=', '')->exists()) {

            return Message::where('fb_reply_id', '!=', '')
                ->orderBy('fb_post_id', 'ASC')
                ->first()->post_date;

        } else if (Reaction::where('fb_post_id', '!=', '')->exists()) {

            return Reaction::where('fb_post_id', '!=', '')
                ->orderBy('fb_post_id', 'DESC')
                ->first()->post_date;

        }
        return Carbon::today();
    }

    /**
     * Answer tweet
     * @param  Request $request
     * @param  string $type
     * @param  integer $toId
     * @param  string $handle
     * @return array
     */
    function answerTweet($request, $type, $toId, $handle)
    {
        $answer = rawurlencode($request->input('answer'));
        $client = initTwitter();

        try {
            if ($type == 'public') {
                $reply = $client->post('statuses/update.json?status=' . $answer . "&in_reply_to_status_id=" . $toId);
            } else {
                $reply = $client->post('direct_messages/new.json?screen_name=' . $handle . '&text=' . $answer);
            }

            Session::flash('flash_success', trans('crm-launcher::success.tweet_sent'));
            return json_decode($reply->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
            return back();
        }
    }

    function answerPost($answer, $messageId)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();

        try {
            $reply = $fb->post('/' . $messageId . '/comments?message=' . rawurlencode($answer) , array('access_token' => $token));
            Session::flash('flash_success', trans('crm-launcher::success.post_sent'));

            return json_decode($reply->getBody());
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Removes _normal from twitter profile link
     * @param  string $link
     * @return string
     */
    function getOriginalImg($link)
    {
        return str_replace('_normal', '', $link);
    }

    /**
     * Checks error code and returns appropriate error
     * @param  integer $code
     * @return string
     */
    function getErrorMessage($code)
    {
        $lang = 'crm-launcher::errors.' . $code;
        $error = trans($lang);

        if (strpos($error, 'crm-launcher') !== false) {
            Session::flash('flash_error', trans('crm-launcher::errors.default'));
        } else {
            Session::flash('flash_error', trans('crm-launcher::errors.' . $code));
        }
    }

    /**
     * Changes Twitter's date format to Y-m-d
     * @param  string $date
     * @return datetime
     */
    function changeDateFormat($date)
    {
        $convertedDate = new DateTime($date);
        $convertedDate->add(date_interval_create_from_date_string('2 hours'));

        return $convertedDate->format("Y-m-d H:i:s");
    }

    /**
     * Changes Facebook's date format Y-m-d
     * @param  string $date
     * @return datetime
     */
    function changeFbDateFormat($date)
    {
         return date("Y-m-d H:i:s", strtotime("+0 hours", strtotime($date)));
    }

}
