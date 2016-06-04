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
use Rubenwouters\CrmLauncher\Models\Configuration;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\InnerComment;
use Rubenwouters\CrmLauncher\Models\InnerAnswer;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\Models\Media;
use Rubenwouters\CrmLauncher\Models\Log;


class CasesController extends Controller
{
    /**
     * Pass active case filters to view
     * @var array
     */
    public static $arActive = [];

    /**
    * Shows cases overview
    * @return view
    */
    public function index()
    {
        $secondsAgo = Log::secondsAgo('fetching');

        if (! $secondsAgo) {
            $this->initIds();
        }

        if ($secondsAgo > 60) {

            if (isFacebookLinked()) {
                $this->collectPrivateConversations();
                $this->collectPosts();
            }

            if (isTwitterLinked()) {
                $this->collectMentions();
                $this->collectDirectMessages();
            }

            Log::updateLog('fetching');
        }
        $cases = CaseOverview::AllCases();
        return view('crm-launcher::cases.index')->with('cases', $cases);
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
            $cases = CaseOverview::orderBy('updated_at', 'DESC')->orderBy('id', 'DESC');
        }

        $cases = $this->searchByCaseType($cases, $request);

        return view('crm-launcher::cases.index')
            ->with('cases', $cases)
            ->with('searchResult', $searchResult)
            ->with('actives', static::$arActive);
    }

    /**
     * Shows detail of case
     * @param  integer $id
     * @return view
     */
    public function detail($id)
    {
        $case = CaseOverview::find($id);
        $handle = $case->contact->twitter_handle;
        $summaries = $case->summaries->sortByDesc('id');

        return view('crm-launcher::cases.detail')
            ->with('case', $case)
            ->with('summaries', $summaries)
            ->with('handle', $handle);
    }

    /**
     * Posts the reply (public or private tweets)
     * @param  Request $request
     * @param  integer  $id
     * @return void
     */
    public function replyTweet(Request $request, $id)
    {
        $case = CaseOverview::find($id);
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

        $reply = answerTweet($request, $type, $tweetId, $handle);
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
        $case = CaseOverview::find($caseId);
        $this->updateLatestHelper($case);

        if ($request->input('in_reply_to') != '') {
            $messageId = $request->input('in_reply_to');
            $answer = $request->input('answer_specific');
        } else {
            $messageId = $case->messages->first()->fb_post_id;
            $answer_to = $case->messages->sortByDesc('id')->first();
            $answer = $request->input('answer');
        }

        $reply = answerPost($answer, $messageId);

        if ($request->input('in_reply_to') != '') {
            $this->insertInnerComment($request, $messageId, $reply);
        } else {
            $this->insertAnswer('facebook_post', $request, $case, $answer_to, $reply, null);
        }

        $this->openCase($case);
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
        $case = CaseOverview::find($caseId);
        $this->updateLatestHelper($case);

        $conversation = $case->messages->sortByDesc('id')->first();
        $answer = $request->input('answer');

        $reply = answerPrivate($conversation, $answer);
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
        $case = CaseOverview::find($caseId);
        $contact = $case->contact;
        $twitterId = $contact->twitter_id;
        toggleFollowUser($contact, $twitterId);

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
        $answer = Answer::find($messageId);
        $case = CaseOverview::find($caseId);

        deleteTweet($case, $answer);

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
        $answer = Answer::find($messageId);
        $answer->delete();

        deleteFbPost($answer);
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
        $comment = InnerComment::find($messageId);
        $comment->delete();

        deleteFbPost($comment);
        return back();
    }

    /**
     * Toggle close/open case
     * @param  integer $caseId
     * @return view
     */
    public function toggleCase($caseId)
    {
        $case = CaseOverview::find($caseId);

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

            $mentionId = newestMentionId();
            $directId = newestDirectId();

            $message->tweet_id = $mentionId;
            $message->direct_id = $directId;
        }

        if (isFacebookLinked()) {
            
            $postId = newestPostId();
            $conversationId = newestConversationId();

            $message->fb_post_id = $postId;
            $message->fb_private_id = $conversationId;
        }

        $message->post_date = Carbon::now();
        $message->save();

        Log::updateLog('fetching');
    }

    /**
     * Searches trough cases
     * @param  Request $request
     * @return Builder
     */
    private function searchByKeywords(Request $request)
    {
        $keywords = $request->input('keywords');
        $contacts = Contact::where('name', 'LIKE', '%' . $keywords . '%')->get();

        if (is_numeric($keywords)) {
            $query = CaseOverview::where('id', $keywords);
        } else if (strpos($keywords, 'tweet') !== false || strpos($keywords, 'twitter') !== false) {
            $query = CaseOverview::where('origin', 'Twitter mention')->orWhere('origin', 'Twitter direct');
        } else if (strpos($keywords, 'fb') !== false || strpos($keywords, 'facebook') !== false || strpos($keywords, 'post') !== false) {
            $query = CaseOverview::where('origin', 'Facebook post');
        }

        if (strtotime($keywords)) {
            $today = date("Y-m-d", strtotime("+0 hours", strtotime($keywords)));
            $tomorrow = date("Y-m-d", strtotime("+1 day", strtotime($keywords)));

            $query = CaseOverview::whereHas('messages', function($q) use ($today, $tomorrow) {
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
            $cases = $cases->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->paginate(12);
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
     * Gets all public mentions on Twitter
     * @return void
     */
    private function collectMentions()
    {
        $mentions = array_reverse(fetchMentions('message'));

        foreach ($mentions as $key => $mention) {

            $date = changeDateFormat($mention['created_at']);
            $inReplyTo = $mention['in_reply_to_status_id_str'];
            $message = new Message();

            if ($inReplyTo == null) {
                $contact = $this->createContact('twitter_mention', $mention);
                $case = $this->createCase('twitter_mention', $mention, $contact);
            }

            if ((Answer::where('tweet_id', $inReplyTo)->exists() || Message::where('tweet_id', $inReplyTo)->exists())
                && $inReplyTo != null
            ) {
                $contact = $this->createContact('twitter_mention', $mention);
                $message->contact_id = $contact->id;

                if (Answer::where('tweet_id', $inReplyTo)->exists()) {
                    $post = Answer::where('tweet_id', $inReplyTo)->first();
                } else {
                    $post = Message::where('tweet_id', $inReplyTo)->first();
                }

                $message->case_id = $post->case_id;
                $case = CaseOverview::find($post->case_id);

            } else if ($inReplyTo != null && Publishment::where('tweet_id', $inReplyTo)->exists()) {
                continue;
            } else if ($inReplyTo != null) {
                $message->tweet_reply_id = $inReplyTo;
            } else {
                $message->case_id = $case->id;
            }

            $message->contact_id = $contact->id;
            $message->tweet_id = $mention['id_str'];
            $message->message = $mention['text'];
            $message->post_date = $date;
            $message->save();

            Media::handleMedia($message->id, $mention, 'twitter');
            $this->updateCase($case->id, 'twitter', $mention['id_str']);
        }
    }

    /**
     * Gets all direct (private) messages on Twitter
     * @return void
     */
    private function collectDirectMessages()
    {
        $sinceId = latestDirect();
        $directs = fetchDirectMessages($sinceId);

        foreach ($directs as $key => $direct) {
            $date = changeDateFormat($direct['created_at']);
            $message = new Message();

            if (Contact::where('twitter_id', $direct['sender']['id_str'])->exists()) {
                $contact = Contact::where('twitter_id', $direct['sender']['id_str'])->first();
                if (count($contact->cases)) {
                    $case = $contact->cases()->where('origin', 'Twitter direct')->orderBy('id', 'DESC')->first();
                } else {
                    $case = $this->createCase('twitter_direct', $direct, $contact);
                }

                $message->case_id = $case->id;
                $this->openCase($case);
            } else {
                $contact = $this->createContact('twitter_direct', $direct);
                $case = $this->createCase('twitter_direct', $direct, $contact);
                $message->case_id = $case->id;
            }

            $message->contact_id = $contact->id;
            $message->direct_id = $direct['id_str'];
            $message->message = $direct['text'];
            $message->post_date = $date;
            $message->save();

            Media::handleMedia($message->id, $direct, 'twitter');
            $this->updateCase($case->id, 'twitter', $direct['id_str']);
        }
    }

    /**
     * Gets all posts on Facebook
     * @return view
     */
    private function collectPosts()
    {
        $newestPost = Message::getNewestPostDate();
        $posts = fetchPosts($newestPost);

        foreach ($posts->data as $key => $post) {
            $contact = $this->createContact('facebook', $post);
            $case = $this->createCase('facebook_post', $post, $contact);

            $message = new Message();
            $message->contact_id = $contact->id;
            $message->fb_post_id = $post->id;
            $message->case_id = $case->id;

            if (isset($post->message)) {
                $message->message = $post->message;
            }
            $message->post_date = changeFbDateFormat($post->created_time);
            $message->save();

            $this->updateCase($case->id, 'facebook', $post->id);
            Media::handleMedia($message->id, $post, 'facebook');
        }

        $newestComment = latestCommentDate();
        $newestInnerComment = InnerComment::LatestInnerCommentDate();
        $this->fetchComments($newestComment);
        $this->fetchInnerComments($newestInnerComment);
    }

    /**
     * Handles all private conversations from Facebook
     * @return void
     */
    private function collectPrivateConversations()
    {
        $newest = Message::getNewestMessageDate();
        $conversations = fetchPrivateConversations();

        foreach ($conversations->data as $key => $conversation) {
            if (changeFbDateFormat($conversation->updated_time) > $newest) {
                $this->collectPrivateMessages($conversation, $newest);
            }
        }
    }

    /**
     * Get the messages out of a conversation
     * @param  object $conversation
     * @param  datetime $newest
     * @return void
     */
    private function collectPrivateMessages($conversation, $newest)
    {
        $messages = fetchPrivateMessages($conversation);

        foreach ($messages->data as $key => $result) {
            if ($result->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                && changeFbDateFormat($result->created_time) > $newest
            ) {
                $contact = $this->createContact('facebook', $result);

                if (CaseOverview::PrivateFbMessages($contact)->exists()) {
                    $case = CaseOverview::PrivateFbMessages($contact)->first();
                    $case->origin = 'Facebook private';
                    $case->contact_id = $contact->id;
                    $case->status = 0;
                    $case->save();
                } else {
                    $case = $this->createCase('facebook_private', $result, $contact);
                }

                $message = new Message();
                $message->contact_id = $contact->id;
                $message->fb_conversation_id = $conversation->id;
                $message->fb_private_id = $result->id;
                $message->case_id = $case->id;
                $message->message = $result->message;
                $message->post_date = changeFbDateFormat($result->created_time);
                $message->save();

                $this->updateCase($case->id, 'facebook', $conversation->id);
                Media::handleMedia($message->id, $result, 'facebook_comment');
            }
        }
    }

    /**
     * Update case table with latest id's added to the case
     * @param  integer $caseId
     * @param  integer $id
     * @return void
     */
    private function updateCase($caseId, $type, $id)
    {
        $case = CaseOverview::find($caseId);

        if ($type == 'facebook') {
            $case->latest_fb_id = $id;
        } else {
            $case->latest_tweet_id = $id;
        }

        $case->save();
    }

    /**
     * Fetch comments on post form Facebook
     * @param  datetime $newest
     * @return return collection
     */
    private function fetchComments($newest)
    {
        $cases = CaseOverview::where('status', '!=', '2')
            ->where('origin', 'Facebook post')
            ->get();

        foreach ($cases as $key => $case) {
            foreach ($case->messages->where('fb_reply_id', '') as $key => $message) {
                $comments = fetchComments($newest, $message);

                if (! empty($comments->data)) {
                    foreach ($comments->data as $key => $comment) {

                        if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                            && new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest)
                        ) {

                            if (Contact::FindByFbId($comment->from->id)->exists()) {
                                $contact = Contact::FindByFbId($comment->from->id)->first();
                            } else {
                                $contact = $this->createContact('facebook', $comment);
                            }

                            $msg = new Message();
                            $msg->fb_reply_id = $message->fb_post_id;
                            $msg->post_date = changeFbDateFormat($comment->created_time);
                            $msg->contact_id = $contact->id;
                            $msg->fb_post_id = $comment->id;
                            $msg->case_id = $case->id;
                            $msg->message = $comment->message;
                            $msg->save();

                            Media::handleMedia($msg->id, $comment, 'facebook_comment');
                            $this->updateCase($case->id, 'facebook', $message->fb_post_id);
                        }
                    }
                }
            }
        }
    }

    /**
     * Fetch inner comments of facebook
     * @param  datetime $newest
     * @return void
     */
    private function fetchInnerComments($newest)
    {
        $messages = Message::where('fb_post_id', '!=', '')->get();
        $answers = Answer::where('fb_post_id', '!=', '')->get();

        $messages = $messages->merge($answers);

        foreach ($messages as $key => $message) {

            $comments = fetchInnerComments($newest, $message['fb_post_id']);

            if($comments == null) {
                continue;
            }

            foreach ($comments->data as $key => $comment) {
                if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                    && new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest)
                ) {
                    if (! Contact::FindByFbId($comment->from->id)->exists()) {
                        $contact = $this->createContact('facebook', $comment);
                    } else {
                        $contact = Contact::FindByFbId($comment->from->id)->first();
                    }

                    $innerComment = new InnerComment();
                    $innerComment->fb_reply_id = $message['fb_post_id'];
                    $innerComment->post_date = changeFbDateFormat($comment->created_time);
                    $innerComment->contact_id = $contact->id;

                    if (is_a($message, "Rubenwouters\CrmLauncher\Models\Answer")) {
                        $innerComment->answer_id = $message['id'];
                    } else {
                        $innerComment->message_id = $message['id'];
                    }

                    $innerComment->fb_post_id = $comment->id;
                    $innerComment->message = $comment->message;
                    $innerComment->save();

                    Media::handleMedia($innerComment->id, $comment, 'facebook_innerComment');
                }
            }
        }
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

        if (Message::where('fb_post_id', $messageId)->exists()) {
            $message = Message::where('fb_post_id', $messageId)->first();
            $innerComment->message_id = $message->id;
        } else {
            $answer = Answer::where('fb_post_id', $messageId)->first();
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
     * Inserts new contact in DB
     * @param  string $type
     * @param  array $message
     * @return object
     */
    private function createContact($type, $message)
    {
        if ($type == "twitter_mention") {

            $contact = $this->getContact('twitter', $message['user']['id_str']);
            $contact->name = $message['user']['name'];
            $contact->twitter_handle = $message['user']['screen_name'];
            $contact->twitter_id =  $message['user']['id_str'];
            $contact->profile_picture = $message['user']['profile_image_url'];

        } else if ($type == "twitter_direct") {

            $contact = $this->getContact('twitter', $message['sender']['id_str']);
            $contact->name = $message['sender']['name'];
            $contact->twitter_handle = $message['sender']['screen_name'];
            $contact->twitter_id =  $message['sender']['id_str'];
            $contact->profile_picture = $message['sender']['profile_image_url'];

        } else if ($type == "facebook") {

            $contact = $this->getContact('facebook', $message->from->id);
            $contact->name = $message->from->name;
            $contact->facebook_id = $message->from->id;
            $contact->profile_picture = getProfilePicture($message->from->id);

        }

        $contact->save();
        return $contact;
    }

    /**
     * Check if contact exists, if not create a new user
     * @param  string  $type
     * @param  string  $id
     * @return collection
     */
    private function getContact($type, $id) {
        if ($type == 'twitter' && Contact::where('twitter_id', $id)->exists()) {
            return Contact::findByTwitterId($id);
        } else if ($type == 'facebook' && Contact::where('facebook_id', $id)->exists()) {
            return Contact::findByFbId($id);
        }

        return new Contact();
    }

    /**
     * Inserts new case in DB
     * @param  string $type
     * @param  array $message
     * @param  object $contact
     * @return object
     */
    private function createCase($type, $message, $contact)
    {
        $case = new CaseOverview();
        $case->contact_id = $contact->id;

        if ($type == 'twitter_mention') {
            $case->origin = "Twitter mention";
        } else if ($type == 'twitter_direct') {
            $case->origin = "Twitter direct";
        } else if ($type == 'facebook_post') {
            $case->origin = "Facebook post";
        } else if ($type == "facebook_private") {
            $case->origin = 'Facebook private';
        }

        $case->status = 0;
        $case->save();

        return $case;
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

        $this->openCase($case);
        $this->linkCaseToUser($case);
    }

    /**
     * Links case to user (person who replied)
     * @param  integer $case
     * @return void
     */
    private function linkCaseToUser($case)
    {
        $case = CaseOverview::find($case->id);
        if (!$case->users->contains(Auth::user()->id)) {
            $case->users()->attach(Auth::user()->id);
        }
    }

    /**
     * Changes status of case to "open"
     * @param  object $case
     * @return void
     */
    private function openCase($case)
    {
        $case->status = 1;
        $case->save();
    }
}
