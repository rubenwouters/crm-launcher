<?php

namespace Rubenwouters\CrmLauncher\ApiCalls;

use Rubenwouters\CrmLauncher\Models\Configuration;
use Session;
use Exception;

class FetchFacebookContent
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @param \Rubenwouters\CrmLauncher\Models\Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Fetch Facebook posts
     *
     * @param object $post
     *
     * @return array|\Illuminate\View\View
     */
    public function fetchFbStats($post)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            $object = $fb->get('/' . $post->fb_post_id . '?fields=shares,likes.summary(true)', $token);

            return json_decode($object->getBody());
        } catch (Exception $e) {
            getErrorMessage($e->getCode());

            return back();
        }
    }

    /**
     * Get Facebook page likes
     *
     * @return array|\Illuminate\View\View
     */
    public function fetchLikes()
    {
        try {
            $fb = initFb();
            $token = $this->config->FbAccessToken();
            $count = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '?fields=fan_count', $token);

            return json_decode($count->getBody(), true);
        } catch (Exception $e) {
            getErrorMessage($e->getCode());

            return back();
        }
    }

    /**
     * Fetch posts from Facebook
     *
     * @param  datetime $newest
     *
     * @return array|\Illuminate\View\View
     */
    public function fetchPosts($newest)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            if ($newest) {
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
     * @return array|\Illuminate\View\View
     */
    public function fetchPrivateConversations()
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

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
     * @return array|\Illuminate\View\View
     */
    public function fetchPrivateMessages($conversation)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

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
     *
     * @param  datetime $newest
     * @param  object $message
     *
     * @return array|\Illuminate\View\View
     */
    public function fetchComments($newest, $message)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

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
     *
     * @param  datetime $newest
     * @param  integer $postId
     *
     * @return array|\Illuminate\View\View
     */
    public function fetchInnerComments($newest, $postId)
    {
        $token = $this->config->FbAccessToken();
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
     *
     * @return integer|bool|\Illuminate\View\View
     */
    public function newestPostId()
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            $posts = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/tagged?fields=from,message,created_time,full_picture&limit=1', $token);
            $posts = json_decode($posts->getBody());

            if (count($posts->data)) {
                return $posts->data[0]->id;
            }

            return false;
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Get newest conversation id
     *
     * @return integer|bool|\Illuminate\View\View
     */
    public function newestConversationId()
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            $privates = $fb->get('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/conversations?fields=id,updated_time&limit=1', $token);
            $posts = json_decode($privates->getBody());

            if (count($posts->data)) {
                return $posts->data[0]->id;
            }

            return false;
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Publish post
     *
     * @param  string $post
     *
     * @return array|\Illuminate\View\View
     */
    public function publishPost($post)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

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
     *
     * @param  string $answer
     * @param  integer $messageId
     *
     * @return array|\Illuminate\View\View
     */
    public function answerPost($answer, $messageId)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            $reply = $fb->post('/' . $messageId . '/comments?message=' . rawurlencode($answer), array('access_token' => $token));
            Session::flash('flash_success', trans('crm-launcher::success.post_sent'));
            return json_decode($reply->getBody());
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Answer private Facebook message
     *
     * @param  object $conversation
     * @param  string $answer
     *
     * @return array|\Illuminate\View\View
     */
    public function answerPrivate($conversation, $answer)
    {
        $fb = initFb();
        $token = $this->config->FbAccessToken();

        try {
            $reply = $fb->post('/' . $conversation->fb_conversation_id . '/messages?message=' . rawurlencode($answer), array('access_token' => $token));
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
     *
     * @param  object $post
     *
     * @return \Illuminate\View\View
     */
    function deleteFbPost($post)
    {
        $token = $this->config->FbAccessToken();
        $fb = initFb();

        try {
            $fb->delete('/' . $post->fb_post_id, ['access_token' => $token]);
            Session::flash('flash_success', trans('crm-launcher::success.post_deleted'));
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
        }

        return back();
    }
}
