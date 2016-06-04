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
 * Check if twitter settings are valid
 * @return boolean
 */
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
 * Fetch all mentions
 * @param  string $type
 * @return array
 */
function fetchMentions($type)
{
    $latestMentionId = latestMentionId($type);

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
 * Fetch all direct (private) messages
 * @param  integer $since_id
 * @return view
 */
function fetchDirectMessages($sinceId)
{
    $client = initTwitter();

    try {
        if ($sinceId != 0) {
            $response = $client->get('direct_messages.json?since_id=' . $sinceId);
        } else {
            $response = $client->get('direct_messages.json?count=1');
        }

        return json_decode($response->getBody(), true);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        getErrorMessage($e->getResponse()->getStatusCode());
        return back();
    }
}


function fetchPosts($newest)
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        if($newest) {
            $posts = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/tagged?fields=from,message,created_time,full_picture&since=' . strtotime($newest), $token);
        } else {
            $posts = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/tagged?fields=from,message,created_time,full_picture&limit=1', $token);
        }
        return json_decode($posts->getBody());
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Fetches all private conversations from Facebook
 * @param  datetime $newest
 * @return array
 */
function fetchPrivateConversations()
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $posts = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/conversations?fields=id,updated_time', $token);
        return json_decode($posts->getBody());
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

function fetchPrivateMessages($conversation)
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $message = $fb->get('/' . $conversation->id . '/messages?fields=from,message,created_time', $token);
        return json_decode($message->getBody());
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Fetch comments on post
 * @param  datetime $newest
 * @param  object $message
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

/**
 * Fetch inner comments
 * @param  datetime $newest
 * @param  integer $postId
 * @return array
 */
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
function latestMentionId($type)
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
 * Get newest mention id on Twitter (Twitter)
 * @return array
 */
function newestMentionId()
{
    $client = initTwitter();

    try {
        $mentions = $client->get('statuses/mentions_timeline.json?count=1');
        return json_decode($mentions->getBody(), true)[0]['id_str'];

    } catch (\GuzzleHttp\Exception\ClientException $e) {

        getErrorMessage($e->getResponse()->getStatusCode());
        return back();

    }
}

/**
 * Get latest inserted direct message ID
 * @return integer
 */
function latestDirect()
{
    $tweet_id = 0;

    if (Message::where('direct_id', '!=', '')->exists()) {
        $tweet_id = Message::LatestDirectId();
    }

    return $tweet_id;
}

/**
 * Get newest direct ID on Twitter (Twitter)
 * @return array
 */
function newestDirectId()
{
    $client = initTwitter();

    try {
        $directs = $client->get('direct_messages.json?count=1');
        return json_decode($directs->getBody(), true)[0]['id_str'];

    } catch (\GuzzleHttp\Exception\ClientException $e) {

        getErrorMessage($e->getResponse()->getStatusCode());
        return back();

    }
}

/**
 * Get newest post ID (Facebook)
 * @return integer
 */
function newestPostId()
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $posts = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/tagged?fields=from,message,created_time,full_picture&limit=1', $token);
        return json_decode($posts->getBody())->data[0]->id;
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Get newest conversation id
 * @return integer
 */
function newestConversationId()
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $privates = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/conversations?fields=id,updated_time&limit=1', $token);
        return json_decode($privates->getBody())->data[0]->id;
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
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

/**
 * Publish tweet
 * @param  Request $request
 * @return view
 */
function publishTweet($tweet)
{
    $client = initTwitter();

    try {
        $publishment = $client->post('statuses/update.json?status=' . $tweet);
        return json_decode($publishment->getBody(), true);

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        getErrorMessage($e->getResponse()->getStatusCode());
        return back();
    }
}

/**
 * Publish post
 * @param  Request $request
 * @return view
 */
function publishPost($post)
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $publishment = $fb->post('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/feed?&message=' . $post, ['access_token' => $token]);
        return json_decode($publishment->getBody());

    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Answer to Facebook post
 * @param  string $answer
 * @param  integer $messageId
 * @return array
 */
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
 * Answer private Facebook message
 * @param  object $conversation
 * @param  string $answer
 * @return array
 */
function answerPrivate($conversation, $answer)
{
    $fb = initFb();
    $token = Configuration::FbAccessToken();

    try {
        $reply = $fb->post('/' . $conversation->fb_conversation_id . '/messages?message=' . rawurlencode($answer) , array('access_token' => $token));
        $reply = json_decode($reply->getBody());
        Session::flash('flash_success', trans('crm-launcher::success.message_sent'));

        return $reply;
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Delete tweet
 * @param  object $case
 * @param  string $answer
 * @return void
 */
function deleteTweet($case, $answer)
{
    $client = initTwitter();

    try {
        if ($case->origin == 'Twitter mention') {
            $client->post('statuses/destroy/' . $answer->tweet_id . '.json');
        } else if ($case->origin == 'Twitter direct') {
            $client->post('direct_messages/destroy.json?id=' . $answer->tweet_id);
        }
        Session::flash('flash_success', trans('crm-launcher::success.tweet_deleted'));

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        getErrorMessage($e->getResponse()->getStatusCode());
        return back();
    }
}

/**
 * Deletes post
 * @param  collection $post
 * @return view
 */
function deleteFbPost($post)
{
    $token = Configuration::FbAccessToken();
    $fb = initFb();

    try {
        $fb->delete('/' . $post->fb_post_id, ['access_token' => $token]);
        Session::flash('flash_success', trans('crm-launcher::success.post_deleted'));
    } catch (Exception $e) {
        getErrorMessage($e->getCode());
        return back();
    }
}

/**
 * Follow/unfollow user
 * @param  object $contact
 * @param  integer $twitterId
 * @return void
 */
function toggleFollowUser($contact, $twitterId)
{
    $client = initTwitter();

    try {
        if ($contact->following) {
            $contact->following = 0;
            $client->post('friendships/destroy.json?follow=true&user_id=' . $twitterId);
            Session::flash('flash_success', trans('crm-launcher::success.unfollow'));
        } else {
            $contact->following = 1;
            $client->post('friendships/create.json?follow=true&user_id=' . $twitterId);
            Session::flash('flash_success', trans('crm-launcher::success.follow'));
        }

        $contact->save();
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        getErrorMessage($e->getResponse()->getStatusCode());
        return back();
    }
}
