<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    public function publishment()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Publishment');
    }

    public function innercomments()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerComment');
    }

    public function media(){
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Media', 'reaction_id');
    }



    public function scopelatestMentionId($query)
    {
        return $query->orderBy('tweet_id', 'DESC')->first()->tweet_id;
    }
}
