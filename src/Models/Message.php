<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Message extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'messages';

    // DB relationships
    public function caseOverview()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\CaseOverview');
    }

    public function answers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Answer');
    }

    public function innerAnswers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerAnswer');
    }

    public function media()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Media');
    }

    public function contact()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Contact');
    }

    public function innerComment()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerComment');
    }


    public function scopelatestMentionId($query)
    {
        return $query->orderBy('tweet_id', 'DESC')->first()->tweet_id;
    }

    public function scopeLatestDirectId($query)
    {
        return $query->where('direct_id', '!=', '')->orderBy('direct_id', 'DESC')->first()->direct_id;
    }


    public function getNewestPostDate()
    {
        if (Message::where('fb_post_id', '!=', '')->exists()) {
            return Message::where('fb_post_id', '!=', '')
                ->orderBy('post_date', 'DESC')
                ->first()->post_date;
        }
        return 0;
    }

    public function getNewestMessageDate()
    {
        if (Message::where('fb_private_id', '!=', '')->exists()) {
            return Message::where('fb_private_id', '!=', '')
                ->orderBy('post_date', 'DESC')
                ->first()->post_date;
        }
        return 0;
    }
}
