<?php

namespace Rubenwouters\CrmLauncher\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use \Exception;
use DateTime;
use Session;
use Auth;
use Rubenwouters\CrmLauncher\Models\Contact;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\InnerComment;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\Models\Log;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class CasesController extends Controller
{
    /**
     * Pass active case filters to view
     * @var array
     */
    public static $arActive = [];

    /**
     * @var Rubenwouters\CrmLauncher\Models\Log
     */
    protected $log;

    /**
     * @var Rubenwouters\CrmLauncher\Models\CaseOverview
     */
    protected $case;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Answer
     */
    protected $answer;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Innercomment
     */
    protected $innerComment;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Message
     */
    protected $message;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Log  $log
     * @param Rubenwouters\CrmLauncher\Models\Case $case
     * @param Rubenwouters\CrmLauncher\Models\Answer $answer
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\Message $message
     * @param Rubenwouters\CrmLauncher\Models\Innercomment $innerComment
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent $twitterContent
     * @param Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent $facebookContent
     */
    public function __construct(
        Log $log,
        CaseOverview $case,
        Answer $answer,
        Contact $contact,
        Message $message,
        InnerComment $innerComment,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->log = $log;
        $this->case = $case;
        $this->answer = $answer;
        $this->innerComment = $innerComment;
        $this->contact = $contact;
        $this->message = $message;
        $this->twitterContent = $twitterContent;
        $this->facebookContent = $facebookContent;
    }

    /**
    * Shows cases overview
    * @return view
    */
    public function index()
    {
        $secondsAgo = $this->log->secondsAgo('fetching');

        if (! $secondsAgo) {
            $this->initIds();
        }
        $cases = $this->case->visibleCases();

        return view('crm-launcher::cases.index')->with('cases', $cases);
    }

    /**
     * Shows detail of case
     * @param  integer $id
     * @return view
     */
    public function detail($id)
    {
        $case = $this->case->find($id);
        $handle = $case->contact->twitter_handle;
        $summaries = $case->summaries->sortByDesc('id');

        return view('crm-launcher::cases.detail')
            ->with('case', $case)
            ->with('summaries', $summaries)
            ->with('handle', $handle);
    }

    /**
     * Filters new/open/closed/own cases
     * @param  Request $request
     * @return view
     */
    public function filter(Request $request)
    {
        $results = $this->searchByKeywords($request);
        $keywords = $request->input('keywords');

        $searchResult['bool'] = true;
        $searchResult['keyword'] = $keywords;

        if ($keywords && $results) {
            $cases = $results;
        } else {
            $searchResult['bool'] = false;
            $cases = $this->case->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC');
        }

        $cases = $this->searchByCaseType($cases, $request);

        return view('crm-launcher::cases.index')
            ->with('cases', $cases)
            ->with('searchResult', $searchResult)
            ->with('actives', static::$arActive);
    }

    /**
     * Posts the reply (public or private tweets)
     * @param  Request $request
     * @param  integer  $id
     * @return void
     */
    public function replyTweet(Request $request, $id)
    {
        $case = $this->case->find($id);
        $handle = $case->contact->twitter_handle;
        $message = $case->messages->sortByDesc('id')->first();

        $this->updateLatestHelper($case);

        if (isset($message->tweet_id)) {
            $tweetId = $message->tweet_id;
        } else {
            getErrorMessage("100");

            return back();
        }

        if ($request->input('in_reply_to', '!=', '')) {
            $tweetId = $request->input('in_reply_to');
        }

        if ($case->origin == 'Twitter mention') {
            $type = 'public';
        } else {
            $type  = 'private';
        }

        $reply = $this->twitterContent->answerTweet($request, $type, $tweetId, $handle);
        $this->insertAnswer('tweet', $request, $case, $message, $reply, $handle);

        return back();
    }

    /**
     * Reply to post on Facebook (either a comment or an inner-comment)
     * @param  Request $request
     * @param  integer $caseId
     * @return void
     */
    public function replyPost(Request $request, $caseId)
    {
        $case = $this->case->find($caseId);
        $this->updateLatestHelper($case);

        if ($request->input('in_reply_to') != '') {
            $messageId = $request->input('in_reply_to');
            $answer = $request->input('answer_specific');
        } else {
            $messageId = $case->messages->first()->fb_post_id;
            $answer_to = $case->messages->sortByDesc('id')->first();
            $answer = $request->input('answer');
        }

        $reply = $this->facebookContent->answerPost($answer, $messageId);

        if ($request->input('in_reply_to') != '') {
            $this->insertInnerComment($request, $messageId, $reply);
        } else {
            $this->insertAnswer('facebook_post', $request, $case, $answer_to, $reply, null);
        }

        $this->case->openCase($case);

        return back();
    }

    /**
     * Reply to private message
     * @param  Request $request
     * @param  integer  $caseId
     * @return view
     */
    public function replyPrivate(Request $request, $caseId)
    {
        $case = $this->case->find($caseId);
        $this->updateLatestHelper($case);

        $conversation = $case->messages->sortByDesc('id')->first();
        $answer = $request->input('answer');

        $reply = $this->facebookContent->answerPrivate($conversation, $answer);
        $this->insertAnswer('facebook_private', $request, $case, $conversation, $reply, null);

        return back();
    }

    /**
     * Follow user on Twitter
     * @param  integer $caseId
     * @return view
     */
    public function toggleFollowUser($caseId)
    {
        $case = $this->case->find($caseId);
        $contact = $case->contact;
        $twitterId = $contact->twitter_id;
        $this->twitterContent->toggleFollowUser($contact, $twitterId);

        return back();
    }

    /**
     * Delete tweet on Twitter & in database
     * @param  integer $caseId
     * @param  integer $messageId
     * @return view
     */
    public function deleteTweet($caseId, $messageId)
    {
        $answer = $this->answer->find($messageId);
        $case = $this->case->find($caseId);
        $this->twitterContent->deleteTweet($case, $answer);
        $answer->delete();

        return back();
    }

    /**
     * Delete Facebook post
     * @param  integer $caseId
     * @param  integer $messageId
     * @return view
     */
    public function deletePost($caseId, $messageId)
    {
        $answer = $this->answer->find($messageId);
        $answer->delete();

        $this->facebookContent->deleteFbPost($answer);

        return back();
    }

    /**
     * Delete inner Facebook post
     * @param  integer $caseId
     * @param  integer $messageId
     * @return view
     */
    public function deleteInner($caseId, $messageId)
    {
        $comment = $this->innerComment->find($messageId);
        $comment->delete();

        $this->facebookContent->deleteFbPost($comment);

        return back();
    }

    /**
     * Toggle close/open case
     * @param  integer $caseId
     * @return view
     */
    public function toggleCase($caseId)
    {
        $case = $this->case->find($caseId);

        if ($case->status == 2) {
            $case->status = 1;
            Session::flash('flash_success', trans('crm-launcher::success.case_reopen'));
        } else {
            $case->status = 2;
            Session::flash('flash_success', trans('crm-launcher::success.case_closed'));
        }
        $case->save();

        return back();
    }

    /**
     * Get most recent id's for Twitter & Facebook
     * @return void
     */
    private function initIds()
    {
        $message = new Message();

        if (isTwitterLinked()) {

            $mentionId = $this->twitterContent->newestMentionId();
            $directId = $this->twitterContent->newestDirectId();

            $message->tweet_id = $mentionId;
            $message->direct_id = $directId;
        }

        if (isFacebookLinked()) {

            $postId = $this->facebookContent->newestPostId();
            $conversationId = $this->facebookContent->newestConversationId();

            $message->fb_post_id = $postId;
            $message->fb_private_id = $conversationId;
        }

        $message->post_date = Carbon::now();
        $message->save();

        $this->log->updateLog('fetching');
    }

    /**
     * Searches trough cases
     * @param  Request $request
     * @return Builder
     */
    private function searchByKeywords(Request $request)
    {
        $keywords = $request->input('keywords');
        $contacts = $this->contact->where('name', 'LIKE', '%' . $keywords . '%')->get();

        if (is_numeric($keywords)) {
            $query = $this->case->where('id', $keywords);
        } else if (strpos($keywords, 'tweet') !== false || strpos($keywords, 'twitter') !== false) {
            $query = $this->case->where('origin', 'Twitter mention')->orWhere('origin', 'Twitter direct');
        } else if (strpos($keywords, 'fb') !== false || strpos($keywords, 'facebook') !== false || strpos($keywords, 'post') !== false) {
            $query = $this->case->where('origin', 'Facebook post');
        }

        if (strtotime($keywords)) {
            $today = date("Y-m-d", strtotime("+0 hours", strtotime($keywords)));
            $tomorrow = date("Y-m-d", strtotime("+1 day", strtotime($keywords)));

            $query = $this->case->whereHas('messages', function($q) use ($today, $tomorrow) {
                $q->orderBy('id', 'ASC')
                    ->where('post_date', '>=', $today)
                    ->where('post_date', '<', $tomorrow);
            });
        }

        foreach ($contacts as $key => $contact) {
            $query = $contact->cases();
        }

        if (! isset($query)) {

            return false;
        }

        return $query;
    }

    /**
     * Filter search results by case type
     * @param  array $cases
     * @param  Request $request
     * @return object
     */
    private function searchByCaseType($cases, $request)
    {
        $caseTypes = $request->input('cases');

        if ($caseTypes != null) {
            $cases->where(function ($q) use ($request) {
                foreach ($request->input('cases') as $i => $value) {
                    $q->orWhere('status', $value);
                    static::$arActive[] = $value;
                }
            });

            $cases = $cases->orderBy('id', 'DESC')->paginate(12);

            if (in_array('my_cases', $caseTypes)) {
                unset($arActive);
                $cases = Auth::user()->cases()->where('status', '1')->paginate(12);
                $arActive[0] = 'my_cases';
            }
        } else {
            $cases = $cases->orderBy('updated_at', 'DESC')->where('status', '!=', '2')->orderBy('id', 'DESC')->paginate(12);
        }

        return $cases;
    }

    /**
     * Update "latest helper" field
     * @param  object $case
     * @return void
     */
    private function updateLatestHelper($case)
    {
        $case->latest_helper = Auth::user()->name;
        $case->save();
    }

    /**
     * Insert inner comment in DB
     * @param  Request $request
     * @param  integer $messageId
     * @param  object $reply
     * @return void
     */
    private function insertInnerComment($request, $messageId, $reply)
    {
        $innerComment = new InnerComment();

        if ($this->message->where('fb_post_id', $messageId)->exists()) {
            $message = $this->message->where('fb_post_id', $messageId)->first();
            $innerComment->message_id = $message->id;
        } else {
            $answer = $this->answer->where('fb_post_id', $messageId)->first();
            $innerComment->answer_id = $answer->id;
        }

        $innerComment->user_id = Auth::user()->id;
        $innerComment->fb_post_id = $reply->id;
        $innerComment->fb_reply_id = $messageId;
        $innerComment->message = $request->input('answer_specific');
        $innerComment->post_date = date('Y-m-d H:i:s');
        $innerComment->save();
    }

    /**
     * Inserts answer to database
     * @param  string $type
     * @param  request $request
     * @param  object $case
     * @param  string $message
     * @param  string $reply
     * @param  string $handle
     * @return void
     */
    private function insertAnswer($type, $request, $case, $message, $reply, $handle)
    {
        $answer = new Answer();
        $answer->case_id = $case->id;
        $answer->user_id = Auth::user()->id;
        $answer->answer = $request->input('answer');
        $answer->post_date = Carbon::now();

        if (is_a($message, "Rubenwouters\CrmLauncher\Models\Answer")) {
            $answer->answer_id = $message->id;
        } else {
            $answer->message_id = $message->id;
        }

        if ($type == 'tweet') {
            $answer->tweet_id = $reply['id_str'];

            if ($case->origin == 'Twitter mention') {
                if ($reply['in_reply_to_status_id_str'] != null) {
                    $answer->tweet_reply_id = $reply['in_reply_to_status_id_str'];
                } else {
                    $answer->tweet_reply_id = 0;
                }
            }
        } else if ($type == 'facebook_post') {
            $answer->fb_post_id = $reply->id;
            $answer->fb_reply_id = $message->fb_post_id;
        } else if ($type == 'facebook_private') {
            $answer->fb_private_id = $reply->id;
            $answer->fb_reply_id = $message->fb_conversation_id;
        }
        $answer->save();

        $this->case->openCase($case);
        $this->linkCaseToUser($case);
    }

    /**
     * Links case to user (person who replied)
     * @param  integer $case
     * @return void
     */
    private function linkCaseToUser($case)
    {
        $case = $this->case->find($case->id);
        if (!$case->users->contains(Auth::user()->id)) {
            $case->users()->attach(Auth::user()->id);
        }
    }
}
