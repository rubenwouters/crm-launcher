<?php

namespace Rubenwouters\CrmLauncher\ApiCalls;

use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Updates\UpdateStatistics;
use Session;

class FetchFacebookContent {

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Update\UpdateStatistics
     */
    protected $stats;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Configuration $config
     */
    public function __construct(Configuration $config, UpdateStatistics $stats)
    {
        $this->config = $config;
        $this->stats = $stats;
    }

    /**
     * Fetch Facebook posts
     * @param  integer $page
     * @return array
     */
    public function fetchFbStats($post)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();

        try {
            $object = $fb->get('/' . $post->fb_post_id . '?fields=shares,likes.summary(true)', $token);

            return json_decode($object->getBody());
        } catch (Exception $e) {
            getErrorMessage($e->getCode());

            return back();
        }
    }

    // /**
    //  * Get Facebook page likes
    //  * @return integer
    //  */
    // public function fetchLikes()
    // {
    //     try {
    //         $fb = initFb();
    //         $token = Configuration::FbAccessToken();
    //         $count = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=fan_count', $token);
    //         $count = json_decode($count->getBody(), true);
    //         $this->stats->updateStats('facebook', $count['fan_count']);
    //
    //         return $count['fan_count'];
    //
    //     } catch (Exception $e) {
    //         getErrorMessage($e->getCode());
    //
    //         return back();
    //     }
    // }

    /**
     * Fetch posts from Facebook
     * @param  datetime $newest
     * @return array
     */
    public function fetchPosts($newest)
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
     * @return array
     */
    public function fetchPrivateConversations()
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

    /**
     * Fetch all private messages
     * @param  object $conversation
     * @return array
     */
    public function fetchPrivateMessages($conversation)
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
    public function fetchComments($newest, $message)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();

        try {
            $comments = $fb->get('/' . $message->fb_post_id . '/comments?fields=from,message,created_time,count,comments,attachment', $token);

            return json_decode($comments->getBody());
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
    public function fetchInnerComments($newest, $postId)
    {
        $token = Configuration::FbAccessToken();
        $fb = initFb();

        try {
            $comments = $fb->get('/' . $postId . '/comments?fields=from,message,created_time,count,comments,attachment', $token);
            return json_decode($comments->getBody());
        } catch (Exception $e) {
            if ($e->getCode() != 100) {
                getErrorMessage($e->getCode());
                return back();
            }
        }
    }

    /**
     * Get newest post ID (Facebook)
     * @return integer
     */
    public function newestPostId()
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
    public function newestConversationId()
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
     * Publish post
     * @param  string $post
     * @return array
     */
    public function publishPost($post)
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
    public function answerPost($answer, $messageId)
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
    public function answerPrivate($conversation, $answer)
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
}
