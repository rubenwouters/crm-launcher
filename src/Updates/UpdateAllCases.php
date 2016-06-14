<?php

namespace Rubenwouters\CrmLauncher\Updates;

use Datetime;
use Rubenwouters\CrmLauncher\Models\Contact;
use Rubenwouters\CrmLauncher\Models\CaseOverview;
use Rubenwouters\CrmLauncher\Models\Publishment;
use Rubenwouters\CrmLauncher\Models\Message;
use Rubenwouters\CrmLauncher\Models\InnerComment;
use Rubenwouters\CrmLauncher\Models\Answer;
use Rubenwouters\CrmLauncher\Models\Media;
use Rubenwouters\CrmLauncher\Models\Reaction;
use Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent;
use Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent;

class UpdateAllCases {

    /**
     * @var Rubenwouters\CrmLauncher\Models\Contact
     */
    protected $contact;

    /**
     * @var Rubenwouters\CrmLauncher\Models\CaseOverview
     */
    protected $case;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Reaction
     */
    protected $reaction;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Publishment
     */
    protected $publishment;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Message
     */
    protected $message;

    /**
     * @var Rubenwouters≈\CrmLauncher\Models\Media
     */
    protected $media;

    /**
     * @var Rubenwouters\CrmLauncher\Models\Answer
     */
    protected $answer;

    /**
     * @var Rubenwouters\CrmLauncher\Models\InnerComment
     */
    protected $innerComment;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchTwitterContent
     */
    protected $twitterContent;

    /**
     * @var Rubenwouters\CrmLauncher\ApiCalls\FetchFacebookContent
     */
    protected $facebookContent;

    /**
     * @param Rubenwouters\CrmLauncher\Models\Contact $contact
     * @param Rubenwouters\CrmLauncher\Models\CaseOverview $case
     * @param Rubenwouters\CrmLauncher\Models\Reaction $reaction
     * @param Rubenwouters\CrmLauncher\Models\Publishment $publishment
     * @param Rubenwouters\CrmLauncher\Models\Message $message
     * @param Rubenwouters\CrmLauncher\Models\Media $media
     * @param Rubenwouters\CrmLauncher\Models\Answer $answer
     * @param Rubenwouters\CrmLauncher\Models\InnerComment $innerComment
     * @param FetchTwitterContent $twitterContent
     * @param FetchFacebookContent $facebookContent
     */
    public function __construct(
        Contact $contact,
        CaseOverview $case,
        Reaction $reaction,
        Publishment $publishment,
        Message $message,
        Media $media,
        Answer $answer,
        InnerComment $innerComment,
        FetchTwitterContent $twitterContent,
        FetchFacebookContent $facebookContent
    ) {
        $this->contact = $contact;
        $this->case = $case;
        $this->reaction = $reaction;
        $this->publishment = $publishment;
        $this->message = $message;
        $this->media = $media;
        $this->answer = $answer;
        $this->innerComment = $innerComment;
        $this->twitterContent = $twitterContent;
        $this->facebookContent = $facebookContent;
    }

    /**
     * Handles all private conversations from Facebook
     * @return void
     */
    public function collectPrivateConversations()
    {
        $newest = $this->message->getNewestMessageDate();
        $conversations = $this->facebookContent->fetchPrivateConversations();

        foreach ($conversations->data as $key => $conversation) {
            if (changeFbDateFormat($conversation->updated_time) > $newest) {
                $this->collectPrivateMessages($conversation, $newest);
            }
        }
    }

    /**
     * Gets all posts on Facebook
     * @return view
     */
    public function collectPosts()
    {
        $newestPost = $this->message->getNewestPostDate();
        $posts = $this->facebookContent->fetchPosts($newestPost);

        foreach ($posts->data as $key => $post) {
            $contact = $this->contact->createContact('facebook', $post);
            $case = $this->case->createCase('facebook_post', $post, $contact);

            $message = new Message();
            $message->contact_id = $contact->id;
            $message->fb_post_id = $post->id;
            $message->case_id = $case->id;

            if (isset($post->message)) {
                $message->message = $post->message;
            }
            $message->post_date = changeFbDateFormat($post->created_time);
            $message->save();

            $this->media->handleMedia($message->id, $post, 'facebook');
        }

        $newestComment = latestCommentDate();
        $newestInnerComment = $this->innerComment->LatestInnerCommentDate();
        $this->fetchComments($newestComment);
        $this->fetchInnerComments($newestInnerComment);
    }

    /**
     * Gets all public mentions on Twitter
     * @return void
     */
    public function collectMentions()
    {
        $mentions = array_reverse($this->twitterContent->fetchMentions());

        foreach ($mentions as $key => $mention) {

            $date = changeDateFormat($mention['created_at']);
            $inReplyTo = $mention['in_reply_to_status_id_str'];
            $message = new Message();

            if ($inReplyTo == null) {
                $contact = $this->contact->createContact('twitter_mention', $mention);
                $case = $this->case->createCase('twitter_mention', $mention, $contact);
            }

            if (($this->answer->where('tweet_id', $inReplyTo)->exists() || $this->message->where('tweet_id', $inReplyTo)->exists())
                && $inReplyTo != null
            ) {
                $contact = $this->contact->createContact('twitter_mention', $mention);
                $message->contact_id = $contact->id;

                if ($this->answer->where('tweet_id', $inReplyTo)->exists()) {
                    $post = $this->answer->where('tweet_id', $inReplyTo)->first();
                } else {
                    $post = $this->message->where('tweet_id', $inReplyTo)->first();
                }

                $message->case_id = $post->case_id;
                $case = $this->case->find($post->case_id);

            } else if ($inReplyTo != null && $this->publishment->where('tweet_id', $inReplyTo)->exists()) {
                $this->fetchSpecificMention($mention);
                continue;
            } else if ($inReplyTo != null) {
                $message->tweet_reply_id = $inReplyTo;
            } else {
                $message->case_id = $case->id;
            }

            $message->contact_id = $contact->id;
            $message->tweet_id = $mention['id_str'];
            $message->message = filterUrl($mention['text']);
            $message->post_date = $date;
            $message->save();

            $this->media->handleMedia($message->id, $mention, 'twitter');
            $this->updateCase($case->id, 'twitter', $mention['id_str']);
        }
    }

    /**
     * Fetch mentions from Twitter
     * @return void
     */
    private function fetchSpecificMention($mention)
    {
        $inReplyTo = $mention['in_reply_to_status_id_str'];
        $publishment = $this->publishment->where('tweet_id', $inReplyTo)->first();

        if (($publishment->tweet_id == $mention['in_reply_to_status_id_str'] || $this->reaction->where('tweet_id', $mention['in_reply_to_status_id_str'])->exists())
            && $publishment->tweet_id != null
        ) {
            $reaction = $this->reaction->insertReaction('twitter', $mention, $publishment->id);
            $this->media->handleMedia($reaction->id, $mention, 'twitter_reaction');
        }
    }

    /**
     * Gets all direct (private) messages on Twitter
     * @return void
     */
    public function collectDirectMessages()
    {
        $sinceId = latestDirect();
        $directs = $this->twitterContent->fetchDirectMessages($sinceId);

        foreach ($directs as $key => $direct) {
            $date = changeDateFormat($direct['created_at']);
            $message = new Message();

            if ($this->contact->where('twitter_id', $direct['sender']['id_str'])->exists()) {
                $contact = $this->contact->where('twitter_id', $direct['sender']['id_str'])->first();
                if (count($contact->cases)) {
                    $case = $contact->cases()->where('origin', 'Twitter direct')->orderBy('id', 'DESC')->first();
                } else {
                    $case = $this->case->createCase('twitter_direct', $direct, $contact);
                }

                $message->case_id = $case->id;
                $this->case->openCase($case);
            } else {
                $contact = $this->contact->createContact('twitter_direct', $direct);
                $case = $this->case->createCase('twitter_direct', $direct, $contact);
                $message->case_id = $case->id;
            }

            $message->contact_id = $contact->id;
            $message->direct_id = $direct['id_str'];
            $message->message = filterUrl($direct['text']);
            $message->post_date = $date;
            $message->save();

            $this->media->handleMedia($message->id, $direct, 'twitter');
            $this->updateCase($case->id, 'twitter', $direct['id_str']);
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
        $messages = $this->facebookContent->fetchPrivateMessages($conversation);

        foreach ($messages->data as $key => $result) {
            if ($result->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                && changeFbDateFormat($result->created_time) > $newest
            ) {
                $contact = $this->contact->createContact('facebook', $result);

                if ($this->case->PrivateFbMessages($contact)->exists()) {
                    $case = $this->case->PrivateFbMessages($contact)->first();
                    $case->origin = 'Facebook private';
                    $case->contact_id = $contact->id;
                    $case->status = 0;
                    $case->save();
                } else {
                    $case = $this->case->createCase('facebook_private', $result, $contact);
                }

                $message = new Message();
                $message->contact_id = $contact->id;
                $message->fb_conversation_id = $conversation->id;
                $message->fb_private_id = $result->id;
                $message->case_id = $case->id;
                $message->message = $result->message;
                $message->post_date = changeFbDateFormat($result->created_time);
                $message->save();

                $this->media->handleMedia($message->id, $result, 'facebook_comment');
            }
        }
    }

    /**
     * Fetch comments on post form Facebook
     * @param  \Datetime $newest
     * @return return collection
     */
    private function fetchComments($newest)
    {
        $messages = $this->getPosts();

        foreach ($messages as $key => $message) {
            if ($message->fb_post_id != 0) {

                $comments = $this->facebookContent->fetchComments($newest, $message);

                if (!empty($comments->data)) {
                    foreach ($comments->data as $key => $comment) {

                        if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                            && (!$newest || new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest))
                        ) {

                            if ($this->contact->FindByFbId($comment->from->id)->exists()) {
                                $contact = $this->contact->where('facebook_id', $comment->from->id)->first();
                            } else {
                                $contact = $this->contact->createContact('facebook', $comment);
                            }

                            if ($this->publishment->where('fb_post_id', $message->fb_post_id)->exists()) {
                                $id = $this->publishment->where('fb_post_id', $message->fb_post_id)->first()->id;
                                $reaction = $this->reaction->insertReaction('facebook', $comment, $id);
                                $this->media->handleMedia($reaction->id, $comment, 'facebook_reactionInner');
                            } else {
                                $msg = new Message();
                                $msg->fb_reply_id = $message->fb_post_id;
                                $msg->post_date = changeFbDateFormat($comment->created_time);
                                $msg->contact_id = $contact->id;
                                $msg->fb_post_id = $comment->id;
                                $msg->case_id = $message->case_id;
                                $msg->message = $comment->message;
                                $msg->save();

                                $this->media->handleMedia($msg->id, $comment, 'facebook_comment');
                                $this->updateCase($message->case_id, 'facebook', $comment->id);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Update case with newest Facebook or Tweet id
     * @param  integer $caseId
     * @param  integer $messageId
     * @param  string $type
     * @return void
     */
    private function updateCase($caseId, $type, $messageId)
    {
        $case = $this->case->find($caseId);

        if ($type == 'twitter') {
            $case->latest_tweet_id = $messageId;
        } else {
            $case->latest_fb_id = $messageId;
        }

        $case->save();
    }

    /**
     * Return all facebook posts (merges messages & publishments)
     * @return [type] [description]
     */
    private function getPosts()
    {
        $collection = collect();
        $messages = $this->message->with(['caseOverview' => function($query) {
                $query->where('status', '!=', '2')->where('origin', 'Facebook post');
            }])->where('fb_post_id', '!=', '')->where('fb_reply_id', '')->where('fb_private_id', '')->get();

        $publishments = $this->publishment->facebookPosts();

        foreach ($messages as $key => $message) {
            $collection->push($message);
        }

        foreach ($publishments as $key => $publishment) {
            $collection->push($publishment);
        }

        return $collection;
    }

    /**
     * Fetch inner comments of facebook
     * @param  datetime $newest
     * @return void
     */
    private function fetchInnerComments($newest)
    {
        $messages = $this->getComments();

        foreach ($messages as $key => $message) {
            $comments = $this->facebookContent->fetchInnerComments($newest, $message['fb_post_id']);

            if ($comments == null) {
                continue;
            }

            foreach ($comments->data as $key => $comment) {
                if ($comment->from->id != config('crm-launcher.facebook_credentials.facebook_page_id')
                    && new Datetime(changeFbDateFormat($comment->created_time)) > new Datetime($newest)
                ) {
                    if (!$this->contact->findByFbId($comment->from->id)->exists()) {
                        $contact = $this->contact->createContact('facebook', $comment);
                    } else {
                        $contact = $this->contact->where('facebook_id', $comment->from->id)->first();
                    }

                    $innerComment = new InnerComment();
                    $innerComment->fb_reply_id = $message['fb_post_id'];
                    $innerComment->post_date = changeFbDateFormat($comment->created_time);
                    $innerComment->contact_id = $contact->id;

                    if (is_a($message, "Rubenwouters\CrmLauncher\Models\Answer")) {
                        $innerComment->answer_id = $message['id'];
                    } else if (is_a($message, "Rubenwouters\CrmLauncher\Models\Reaction")) {
                        $innerComment->reaction_id = $message['id'];
                    } else {
                        $innerComment->message_id = $message['id'];
                    }

                    $innerComment->fb_post_id = $comment->id;
                    $innerComment->message = $comment->message;
                    $innerComment->save();

                    $this->media->handleMedia($innerComment->id, $comment, 'facebook_innerComment');
                }
            }
        }
    }

    private function getComments()
    {
        $collection = collect();
        $messages = $this->message->where('fb_post_id', '!=', '')->get();
        $answers = $this->answer->where('fb_post_id', '!=', '')->get();
        $reactions = $this->reaction->where('fb_post_id', '!=', '')->get();

        foreach ($messages as $key => $message) {
            $collection->push($message);
        }

        foreach ($answers as $key => $answer) {
            $collection->push($answer);
        }

        foreach ($reactions as $key => $reaction) {
            $collection->push($reaction);
        }

        return $collection;
    }
}
