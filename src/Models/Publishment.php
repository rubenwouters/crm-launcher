<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Publishment extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'publishments';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of Publishment model
    |
    */
    public function reactions()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Reaction');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes of Publishment model
    |
    */

    public function scopeDouble($query, $content)
    {
        return $query->where('content', $content);
    }

    public function scopeFacebookPosts($query)
    {
        return $query->where('fb_post_id', '!=', '')->get();
    }
}
