<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'media';

    public function message()
    {
        return $this->belongsTo('App\Message');
    }

    public function innerComment()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\InnerComment');
    }

    public function reaction()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Reaction');
    }

    /**
     * Handles media if sent with tweet
     * @param  integer $messageId
     * @param  collection $mention
     * @return void
     */
    public static function handleMedia($messageId, $message, $type = null)
    {
        $msg = Message::find($messageId);
        if ($type == 'innerComment') {
            $msg = InnerComment::find($messageId);
        }

        if (($type == 'twitter' || $type == 'twitter_reaction') && ! empty($message['extended_entities']) && !empty($message['extended_entities']['media'])) {
            foreach ($message['extended_entities']['media'] as $key => $picture) {
                $media = new Media();
                if ($type == 'twitter_reaction') {
                    $media->reaction_id = $messageId;
                } else {
                    $media->message_id = $messageId;
                }
                $media->url = $picture['media_url'];
                $media->save();
            }
        } else if (($type == 'twitter' || $type == 'twitter_reaction') && ! empty($message['entities']) && !empty($message['entities']['media'])) {
            foreach ($message['entities']['media'] as $key => $picture) {
                $media = new Media();
                if ($type == 'twitter_reaction') {
                    $media->reaction_id = $messageId;
                } else {
                    $media->message_id = $messageId;
                }
                $media->url = $picture['media_url'];
                $media->save();
            }
        } else if (($type == 'facebook_comment' || $type == 'facebook_innerComment' || $type == 'facebook_reactionInner') && isset($message->attachment->media->image->src)) {
            $media = new Media();
            if ($type == 'facebook_comment') {
                $media->message_id = $messageId;
            } else if ($type == 'facebook_innerComment') {
                $media->inner_comment_id = $messageId;
            } else {
                $media->reaction_id = $messageId;
            }
            $media->url = $message->attachment->media->image->src;
            $media->save();
        } else if ($type == 'facebook' && isset($message->full_picture)) {
            $media = new Media();
            $media->message_id = $messageId;
            $media->url = $message->full_picture;
            $media->save();
        }
    }
}
