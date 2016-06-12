<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\Models\Reaction;
use Rubenwouters\CrmLauncher\Models\InnerComment;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class PublishController extends Controller
{
    const TYPE_TWITTER = 'twitter';
    const TYPE_FACEBOOK = 'facebook';
    use ValidatesRequests;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Reaction
     */
    protected $reaction;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Publishment
     */
    protected $publishment;

    /**
     * @var FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * @var FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Reaction $reaction
     * @param Rubenwouters\CrmLauncher\Models\Publishment $publishment
     * @param FetchTwitterContent $twitterContent
     * @param FetchFacebookContent $facebookContent
     */
    public function __construct(
        Reaction $reaction,
        Publishment $publishment,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->publishment = $publishment;
        $this->reaction = $reaction;
        $this->twitterContent = $twitterContent;
        $this->facebookContent = $facebookContent;
    }

    /**
     * Show start view of publisher
     *
     * @return view
     */
    public function index()
    {
        $publishments = $this->publishment->orderBy('id', 'DESC')->paginate(5);

        return view('crm-launcher::publisher.index')->with('publishments', $publishments);
    }

    /**
     * Show detail of publishment
     *
     * @param  integer $id
     *
     * @return view
     */
    public function detail($id)
    {
        $publishment = $this->publishment->find($id);

        $data = [
            'publishment' => $publishment,
            'tweets' => $publishment->reactions()->where('tweet_id', '!=', '')->get(),
            'posts' => $publishment->reactions()->where('fb_post_id', '!=', '')->get(),
        ];

        return view('crm-launcher::publisher.detail', $data);
    }

    /**
     * Reply tweet (Twitter)
     *
     * @param  Request $request
     * @param  integer  $id
     *
     * @return view
     */
    public function replyTweet(Request $request, $id)
    {
        $this->validate($request, [
            'answer' => 'required',
        ]);

        $publishment = $this->publishment->find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
        } else {
            $replyTo = $publishment->tweet_id;
        }

        $reply = $this->twitterContent->answerTweet($request, 'public', $replyTo, null);
        $this->reaction->insertReaction(self::TYPE_TWITTER, $reply, $id);

        return back();
    }

    /**
     * Reply post (Facebook)
     *
     * @param  Request $request
     * @param  integer  $id
     *
     * @return view
     */
    public function replyPost(Request $request, $id)
    {
        $this->validate($request, [
            'answer' => 'required',
        ]);

        $publishment = $this->publishment->find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
            $reply = $this->facebookContent->answerPost($request->input('answer'), $replyTo);
            $this->insertInnerComment($id, $request, $replyTo, $reply);
        } else {
            $replyTo = $publishment->fb_post_id;
            $reply = $this->facebookContent->answerPost($request->input('answer'), $replyTo);
            $this->reaction->insertReaction(self::TYPE_FACEBOOK, $reply, $id, $request->input('answer'));
        }

        return back();
    }

    /**
     * Publish Tweet and/or Facebook post
     *
     * @param  Request $request
     *
     * @return view
     */
    public function publish(Request $request)
    {
        $this->validate($request, [
            'content' => 'required',
            'social' => 'required',
        ]);

        $content = rawurlencode($request->input('content'));

        if (in_array(self::TYPE_TWITTER, $request->input('social'))) {
            $publishment = $this->twitterContent->publishTweet($content);
            $this->insertPublishment(self::TYPE_TWITTER, $publishment, $content);
        }

        if (in_array(self::TYPE_FACEBOOK, $request->input('social'))) {
            $publishment = $this->facebookContent->publishPost($content);
            $this->insertPublishment(self::TYPE_FACEBOOK, $publishment, $content);
        }

        return back();
    }

    /**
     * Insert inner comment in DB
     * @param  integer $id
     * @param  Request $request
     * @param  integer $messageId
     * @param  object $reply
     * @return void
     */
    private function insertInnerComment($id, $request, $messageId, $reply)
    {
        $innerComment = new InnerComment();

        if ($this->reaction->where('fb_post_id', $messageId)->exists()) {
            $innerComment->reaction_id = $this->reaction->where('fb_post_id', $messageId)->first()->id;
        }

        $innerComment->user_id = Auth::user()->id;
        $innerComment->fb_post_id = $reply->id;
        $innerComment->fb_reply_id = $messageId;
        $innerComment->message = $request->input('answer');
        $innerComment->post_date = Carbon::now();
        $innerComment->save();
    }

    /**
     * Insert publishment in DB
     *
     * @param string $type
     * @param array $publication
     * @param string $content
     */
    private function insertPublishment($type, $publication, $content)
    {
        if ($this->publishment->double($content)->exists()) {
            $publishment = $this->publishment->double($content)->first();
        } else {
            $publishment = new Publishment();
        }

        $publishment->user_id = Auth::user()->id;
        $publishment->content = $content;

        if ($type == self::TYPE_TWITTER) {
            $publishment->tweet_id = $publication['id_str'];
            $publishment->post_date = changeDateFormat($publication['created_at']);
        } else if ($type == self::TYPE_FACEBOOK) {
            $publishment->fb_post_id = $publication->id;
            $publishment->post_date = Carbon::now();
        }

        $publishment->save();
    }
}
