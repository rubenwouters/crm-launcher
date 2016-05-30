<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Publishment extends Model
{
    public function reactions()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Reaction');
    }

    public function scopeDouble($query, $content)
    {
        return $query->where('content', $content);
    }

    public function scopeFacebookPosts($query)
    {
        return $query->where('fb_post_id', '!=', '')->get();
    }
}
