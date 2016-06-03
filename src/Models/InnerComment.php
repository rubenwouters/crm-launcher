<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InnerComment extends Model
{
    protected $table = 'inner_comments';
    protected $primaryKey = 'id';

    // DB relationships
    public function caseOverview(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\CaseOverview');
    }

    public function answers(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Answer');
    }

    public function innerAnswers(){
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerAnswer');
    }

    public function media(){
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Media', 'inner_comment_id');
    }

    public function contact(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Contact');
    }

    public function message(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Message');
    }

    public function reaction(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Reaction');
    }


    public function scopeLatestInnerCommentDate($query)
    {
        if ($query->where('reaction_id', '0')->exists()) {
            return $query->orderBy('id', 'DESC')->where('reaction_id', '0')->first()->post_date;
        }

        return Carbon::today();
    }
}
