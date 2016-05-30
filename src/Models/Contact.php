<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{

    // DB relationships
    public function cases()
    {
       return $this->hasMany('Rubenwouters\CrmLauncher\Models\CaseOverview');
    }

    public function messages()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Message');
    }

    public function innerComment()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerComment');
    }


    public function scopeFindByFbId($query, $id)
    {
        return $query->where('facebook_id', $id)->orderBy('id', 'DESC')->first();
    }

    public function scopeFindByTwitterId($query, $id)
    {
        return $query->where('twitter_id', $id)->first();
    }

}
