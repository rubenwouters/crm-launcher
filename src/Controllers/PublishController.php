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
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\Models\Message;
use Auth;
use Session;
use Carbon\Carbon;
use \Exception;
use Datetime;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class PublishController extends Controller
{
    use ValidatesRequests;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $log;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\Models\Reaction
     */
    protected $reaction;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * Contact implementation
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\Case $case
     */
    public function __construct(
        Log $log,
        Reaction $reaction,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->log = $log;
        $this->reaction = $reaction;
        $this->twitterContent = $twitterContent;
        $this->facebookContent = $facebookContent;
    }

    /**
     * Show start view of publisher
     * @return view
     */
    public function index()
    {
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

        $data = [
            'publishment' => $publishment,
            'tweets' => $publishment->reactions()->where('tweet_id', '!=', '')->get(),
            'posts' => $publishment->reactions()->where('fb_post_id', '!=', '')->get(),
        ];

        return view('crm-launcher::publisher.detail', $data);
    }

    /**
     * Reply tweet (Twitter)
     * @param  Request $request
     * @param  integer  $id
     * @return view
     */
    public function replyTweet(Request $request, $id)
    {
        $this->validate($request, [
            'answer' => 'required',
        ]);

        $publishment = Publishment::find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
        } else {
            $replyTo = $publishment->tweet_id;
        }

        $reply = $this->twitterContent->answerTweet($request, 'public', $replyTo, null);
        $this->reaction->insertReaction('twitter', $reply, $id);

        return back();
    }

    /**
     * Reply post (Facebook)
     * @param  Request $request
     * @param  integer  $id
     * @return view
     */
    public function replyPost(Request $request, $id)
    {
        $this->validate($request, [
            'answer' => 'required',
        ]);
        
        $publishment = Publishment::find($id);

        if ($request->input('in_reply_to') != "") {
            $replyTo = $request->input('in_reply_to');
            $reply = $this->facebookContent->answerPost($request->input('answer'), $replyTo);
            $this->insertInnerComment($id, $request, $replyTo, $reply);
        } else {
            $replyTo = $publishment->fb_post_id;
            $reply = $this->facebookContent->answerPost($request->input('answer'), $replyTo);
            $this->reaction->insertReaction('facebook', $reply, $id, $request->input('answer'));
        }

        return back();
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

        $content = rawurlencode($request->input('content'));

        if (in_array('twitter', $request->input('social'))) {
            $publishment = $this->twitterContent->publishTweet($content);
            $this->insertPublishment('twitter', $publishment, $content);
        }

        if (in_array('facebook', $request->input('social'))) {
            $publishment = $this->facebookContent->publishPost($content);
            $this->insertPublishment('facebook', $publishment, $content);
        }

        return back();
    }

    /**
     * Insert inner comment in DB
     * @param  integer $id
     * @param  Request $request
     * @param  id $messageId
     * @param  object $reply
     * @return void
     */
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
}
