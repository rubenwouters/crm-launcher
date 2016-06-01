<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\Models\Reaction;
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Models\InnerComment;
use Rubenwouters\CrmLauncher\Models\Media;
use Rubenwouters\CrmLauncher\Models\Log;
use Auth;
use Session;
use Carbon\Carbon;
use \Exception;
use Datetime;
use Illuminate\Foundation\Validation\ValidatesRequests;

class PublishController extends Controller
{
    use ValidatesRequests;

    /**
     * Show start view of publisher
     * @return view
     */
    public function index()
    {
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        } else {
            $page = 1;
        }

        $secondsAgo = Log::secondsAgo('publishments');
        if (! $secondsAgo || $secondsAgo > 30) {
            $tweets = $this->fetchTwitterStats($page);
            $this->updateTwitterStats($tweets);
            $posts = $this->fetchFbStats($page);

            Log::updateLog('publishments');
        }

        $publishments = Publishment::orderBy('id', 'DESC')->paginate(5);

        return view('crm-launcher::publisher.index')->with('publishments', $publishments);
    }

    /**
     * Show detail of publishment
     * @param  integer $id
     * @return view
     */
    public function detail($id)
    {
        $publishment = Publishment::find($id);
        $secondsAgo = Log::secondsAgo('publishment_detail');

        if (! $secondsAgo || $secondsAgo > 60) {
            Log::updateLog('publishment_detail');
            $this->fetchMentions($id);
            $this->fetchPosts($id);
            $this->fetchInnerComments($id);
        }

        $tweets = $publishment->reactions()->where('tweet_id', '!=', '')->get();
        $posts = $publishment->reactions()->where('fb_post_id', '!=', '')->get();

        return view('crm-launcher::publisher.detail')
            ->with('tweets', $tweets)
            ->with('posts', $posts)
            ->with('publishment', $publishment);
    }

    /**
     * Reply tweet
     * @param  Request $request
     * @param  integer  $id
     * @return view
     */
    public function replyTweet(Request $request, $id)
    {
        $publishment = Publishment::find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
        } else {
            $replyTo = $publishment->tweet_id;
        }

        $reply = answerTweet($request, 'public', $replyTo, null);
        $this->insertReaction('twitter', $reply, $id);
        return back();
    }

    public function replyPost(Request $request, $id)
    {
        $publishment = Publishment::find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
            $reply = answerPost($request->input('answer'), $replyTo);
            $this->insertInnerComment($id, $request, $replyTo, $reply);
        } else {
            $replyTo = $publishment->fb_post_id;
            $reply = answerPost($request->input('answer'), $replyTo);
            $this->insertReaction('facebook', $reply, $id, $request->input('answer'));
        }


        return back();
    }

    private function insertInnerComment($id, $request, $messageId, $reply)
    {
        $innerComment = new InnerComment();

        if (Reaction::where('fb_post_id', $messageId)->exists()) {
            $innerComment->reaction_id = Reaction::where('fb_post_id', $messageId)->first()->id;
        }

        $innerComment->user_id = Auth::user()->id;
        $innerComment->fb_post_id = $reply->id;
        $innerComment->fb_reply_id = $messageId;
        $innerComment->message = $request->input('answer');
        $innerComment->post_date = Carbon::now();
        $innerComment->save();
    }

    private function insertReaction($type, $mention, $id, $answer = null)
    {
        $reaction = new Reaction();
        $reaction->publishment_id = $id;

        if ($answer != null) {
            $reaction->user_id = Auth::user()->id;
        }

        if ($type == 'twitter') {
            if ($mention['user']['id_str'] != Configuration::twitterId()) {
                $reaction->user_id = $mention['user']['id_str'];
            }

            $reaction->screen_name = $mention['user']['screen_name'];
            $reaction->tweet_id = $mention['id_str'];
            $reaction->tweet_reply_id = $mention['in_reply_to_status_id_str'];
            $reaction->message = $mention['text'];
            $reaction->post_date = changeDateFormat($mention['created_at']);
        } else {
            $reaction->fb_post_id = $mention->id;

            if ($answer == null) {
                $reaction->message = $mention->message;
                $reaction->post_date = changeFbDateFormat($mention->created_time);
            } else {
                $reaction->message = $answer;
                $reaction->post_date = Carbon::now();
            }
        }

        $reaction->save();
        return $reaction;
    }

    /**
     * Publish Tweet and/or Facebook post
     * @param  Request $request
     * @return view
     */
    public function publish(Request $request)
    {
        $this->validate($request, [
            'content' => 'required',
            'social' => 'required',
        ]);

        if (in_array('twitter', $request->input('social'))) {
            $this->publishTweet($request);
        }

        if (in_array('facebook', $request->input('social'))) {
            $this->publishPost($request);
        }

        return back();
    }

    /**
     * Publish tweet
     * @param  Request $request
     * @return view
     */
    private function publishTweet($request)
    {
        $client = initTwitter();
        $tweet = rawurlencode($request->input('content'));

        try {
            $publishment = $client->post('statuses/update.json?status=' . $tweet);
            $publishment = json_decode($publishment->getBody(), true);
            $this->insertPublishment('twitter', $publishment, $tweet);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
            return back();
        }
    }

    /**
     * Fetch mentions from Twitter
     * @return void
     */
    private function fetchMentions($id)
    {
        $mentions = fetchMentions('reaction');
        $publishment = Publishment::find($id);

        foreach ($mentions as $key => $mention) {
            if ($publishment->tweet_id == $mention['in_reply_to_status_id_str'] || Reaction::where('tweet_id', $mention['in_reply_to_status_id_str'])->exists()) {
                $reaction = $this->insertReaction('twitter', $mention, $id);
                Media::handleMedia($reaction->id, $mention, 'twitter_reaction');
            }
        }
    }

    /**
     * Fetch posts from Facebook
     * @return void
     */
    private function fetchPosts($id)
    {
        $publishments = Publishment::facebookPosts();

        foreach ($publishments as $key => $publishment) {
            $newest = latestCommentDate();
            $comments = fetchComments($newest, $publishment);

            if ($comments != null) {
                foreach ($comments->data as $key => $comment) {
                    if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                        && new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest)
                    ) {
                        $reaction = $this->insertReaction('facebook', $comment, $id);
                        Media::handleMedia($reaction->id, $comment, 'facebook_reactionInner');
                    }
                }
            }
        }
    }

    private function fetchInnerComments($id)
    {
        $reactions = Publishment::find($id)->reactions()->where('fb_post_id', '!=', '')->get();
        $newest = InnerComment::LatestInnerCommentDate();

        foreach ($reactions as $key => $reaction) {

            $comments = fetchInnerComments($newest, $reaction->fb_post_id);
            foreach ($comments->data as $key => $comment) {

                if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                    && new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest)
                ) {

                    $innerComment = new InnerComment();
                    $innerComment->fb_post_id = $comment->id;
                    $innerComment->fb_reply_id = $reaction->fb_post_id;
                    $innerComment->post_date = changeFbDateFormat($comment->created_time);
                    $innerComment->reaction_id = $reaction->id;
                    $innerComment->message = $comment->message;
                    $innerComment->save();

                    Media::handleMedia($innerComment->id, $comment, 'facebook_innerComment');
                }
            }
        }
    }

    /**
     * Publish post
     * @param  Request $request
     * @return view
     */
    private function publishPost($request)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();
        $post = rawurlencode($request->input('content'));

        try {
            $publishment = $fb->post('/' . config('crm-launcher.facebook_credentials.facebook_page_id') . '/feed?&message=' . $post, ['access_token' => $token]);
            $publishment = json_decode($publishment->getBody());
            $this->insertPublishment('facebook', $publishment, $post);

        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }
    }

    /**
     * Insert publishment in DB
     * @param  array $publishment
     * @return void
     */
    private function insertPublishment($type, $publication, $content)
    {
        if (Publishment::double($content)->exists()) {
            $publishment = Publishment::double($content)->first();
        } else {
            $publishment = new Publishment();
        }

        $publishment->user_id = Auth::user()->id;
        $publishment->content = $content;

        if ($type == 'twitter') {
            $publishment->tweet_id = $publication['id_str'];
            $publishment->post_date = changeDateFormat($publication['created_at']);
        } else if ($type == 'facebook') {
            $publishment->fb_post_id = $publication->id;
            $publishment->post_date = Carbon::now();
        }

        $publishment->save();
    }

    /**
     * Fetch user's tweets
     * @return array
     */
    private function fetchTwitterStats($page)
    {
        $client = initTwitter();
        $twitterId = Configuration::twitterId();

        $page = (5 * $page) - 5;

        if(Publishment::exists()) {
            $maxId = Publishment::orderBy('id', 'DESC')->skip($page)->first()->tweet_id;
        } else {
            $maxId = 0;
        }

        try {
            $tweets = $client->get('statuses/user_timeline.json?count=5&user_id=' . $twitterId . '&max_id=' . $maxId);
            return json_decode($tweets->getBody(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            getErrorMessage($e->getResponse()->getStatusCode());
            return back();
        }
    }

    /**
     * Update stats in DB (like count & retweet count)
     * @param  array $tweets
     * @return void
     */
    private function updateTwitterStats($tweets)
    {
        foreach ($tweets as $key => $tweet) {
            if (Publishment::where('tweet_id', $tweet['id_str'])->exists()) {
                $publishment = Publishment::where('tweet_id', $tweet['id_str'])->first();
                $publishment->twitter_likes = $tweet['favorite_count'];
                $publishment->twitter_retweets = $tweet['retweet_count'];
                $publishment->save();
            }
        }
    }

    /**
     * Fetch Facebook posts
     * @return array
     */
    private function fetchFbStats($page)
    {
        $fb = initFb();
        $token = Configuration::FbAccessToken();

        if ($page) {
            $page = (5 * $page) - 5;
        } else {
            $page = 0;
        }

        $posts = Publishment::orderBy('id', 'DESC')
            ->where('fb_post_id', '!=', '')
            ->skip($page)
            ->take(5)->get();
        try {
            foreach ($posts as $key => $post) {
                $object = $fb->get('/' . $post->fb_post_id . '?fields=shares,likes.summary(true)', $token);
                $object = json_decode($object->getBody());
                $this->updateFbStats($post, $object);
            }
        } catch (Exception $e) {
            getErrorMessage($e->getCode());
            return back();
        }

    }

    /**
     * Update stats in DB
     * @param  array $posts
     * @return void
     */
    private function updateFbStats($post, $object)
    {
        if (isset($object->shares)) {
            $post->facebook_shares = $object->shares->count;
        }
        $post->facebook_likes = $object->likes->summary->total_count;
        $post->save();
    }
}
