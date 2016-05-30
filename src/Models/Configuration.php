<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    protected $table = 'configurations';
    protected $primaryKey = 'id';

    public function scopeFbAccessToken($query)
    {
        return $query->first()->facebook_access_token;
    }

    public function scopeTwitterId($query)
    {
        return $query->first()->twitter_id;
    }

    public function scopeFollowers($query)
    {
        return $query->first()->twitter_followers;
    }

    public function scopeLikes($query)
    {
        return $query->first()->facebook_likes;
    }
}
