<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Rubenwouters\CrmLauncher\Models\Reaction;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Carbon\Carbon;

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

function filterUrl($message)
{
    $pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
    return preg_replace($pattern, '', $message);
}

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

/**
 * Check if configuration is done and successful
 * @return boolean
 */
function isConfigured()
{
    if (Configuration::exists() && Configuration::first()->valid_credentials) {
        return true;
    }

    return false;
}

/**
 * Check if Twitter is used
 * @return boolean
 */
function isTwitterLinked()
{
    if (Configuration::exists()) {
        return Configuration::first()->linked_twitter;
    }

    return false;
}

/**
 * Check if Facebook is linked
 * @return boolean
 */
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
 * Get profile picture of user on Facebook
 * @param  integer $id
 * @return view
 */
function getProfilePicture($id)
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $picture = $fb->get('/' . $id . '/picture?redirect=false&type=large', $token);
        $picture = json_decode($picture->getBody());
        return $picture->data->url;
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Gets latest inserted mention id (Twitter).
 * @return id
 */
function latestMentionId()
{
    $messageId = $reactionId = false;

    if (Message::where('tweet_id', '!=', '')->exists()) {
        $messageId = Message::latestMentionId();
    }
    if (Reaction::where('tweet_id', '!=', '')->exists()) {
        $reactionId = Reaction::latestMentionId();
    }

    return max($messageId, $reactionId);
}

/**
 * Get latest inserted direct message ID
 * @return integer
 */
function latestDirect()
{
    $tweet_id = 0;

    if (Message::where('direct_id', '!=', '')->where('direct_id', '!=', '0')->exists()) {
        $tweet_id = Message::LatestDirectId();
    }

    return $tweet_id;
}

/**
 * Get latest comment date (Facebook)
 * @return datetime
 */
function latestCommentDate()
{
    $messageId = $reactionId = false;

    if (Message::where('fb_reply_id', '!=', '')->exists()) {
        $messageId =  Message::where('fb_reply_id', '!=', '')->orderBy('post_date', 'DESC')->first()->post_date;
    }
    if (Reaction::where('fb_post_id', '!=', '')->exists()) {
        $reactionId = Reaction::where('fb_post_id', '!=', '')->orderBy('post_date', 'DESC')->first()->post_date;
    }

    var_dump($messageId, $reactionId, max($messageId, $reactionId));
    return max($messageId, $reactionId);
}
