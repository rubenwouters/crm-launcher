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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of Media model
    |
    */
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
     *
     * @param  integer $messageId
     * @param array $message
     * @param null $type
     */
    public function handleMedia($messageId, $message, $type = null)
    {
        if (($type == TWITTER || $type == TWITTER_REACTION) && ! empty($message['extended_entities']) && !empty($message['extended_entities']['media'])) {
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
        } else if (($type == TWITTER || $type == TWITTER_REACTION) && ! empty($message['entities']) && !empty($message['entities']['media'])) {
            foreach ($message['entities']['media'] as $key => $picture) {
                $media = new Media();
                if ($type == TWITTER_REACTION) {
                    $media->reaction_id = $messageId;
                } else {
                    $media->message_id = $messageId;
                }
                $media->url = $picture['media_url'];
                $media->save();
            }
        } else if (($type == FACEBOOK_COMMENT || $type == FACEBOOK_INNERCOMMENT || $type == FACEBOOK_REACTIONINNER) && isset($message->attachment->media->image->src)) {
            $media = new Media();
            if ($type == FACEBOOK_COMMENT) {
                $media->message_id = $messageId;
            } else if ($type == FACEBOOK_INNERCOMMENT) {
                $media->inner_comment_id = $messageId;
            } else {
                $media->reaction_id = $messageId;
            }
            $media->url = $message->attachment->media->image->src;
            $media->save();
        } else if ($type == FACEBOOK && isset($message->full_picture)) {
            $media = new Media();
            $media->message_id = $messageId;
            $media->url = $message->full_picture;
            $media->save();
        }
    }
}
