<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;

class Reaction extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'reactions';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of Reaction model
    |
    */
    public function publishment()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Publishment');
    }

    public function innercomments()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerComment');
    }

    public function media()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Media', 'reaction_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes of Reaction model
    |
    */

    public function scopelatestMentionId($query)
    {
        return $query->orderBy('tweet_id', 'DESC')->first()->tweet_id;
    }

    /**
     * Insert reaction in DB (eiter from a Facebook post or Tweet)
     * @param  string $type
     * @param  object $mention
     * @param  integer $id
     * @param  string $answer
     *
     * @return Reaction
     */
    public function insertReaction($type, $mention, $id, $answer = null)
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
                $reaction->screen_name = $mention->from->name;
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
}
