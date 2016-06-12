<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InnerComment extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'inner_comments';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of InnerComment model
    |
    */
    public function caseOverview()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\CaseOverview');
    }

    public function answers()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Answer');
    }

    public function innerAnswers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerAnswer');
    }

    public function media()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Media', 'inner_comment_id');
    }

    public function contact()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Contact');
    }

    public function message()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Message');
    }

    public function reaction()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Reaction');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope
    |--------------------------------------------------------------------------
    |
    | Scope of InnerComment model
    |
    */

    public function scopeLatestInnerCommentDate($query)
    {
        $messageId = $reactionId = Carbon::today();

        if ($query->where('reaction_id', '0')->exists()) {
            $messageId = $query->orderBy('post_date', 'DESC')->where('reaction_id', '0')->first()->post_date;
        }

        if ($query->where('reaction_id', '!=', '0')->exists()) {
            $reactionId = $query->orderBy('post_date', 'DESC')->where('reaction_id', '!=', '0')->first()->post_date;
        }

        return max($messageId, $reactionId);
    }
}
