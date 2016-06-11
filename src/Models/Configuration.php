<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'configurations';

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes of Configuration model
    |
    */
    public function scopeFbAccessToken($query)
    {
        return $query->first()->facebook_access_token;
    }

    public function scopeTwitterId($query)
    {
        return $query->first()->twitter_id;
    }

    /**
     * Inserts Twitter id & screen name in configuration table
     * @param array $verification
     * @return void
     */
    public function insertTwitterId($verification)
    {
        $config = Configuration::first();
        $config->twitter_screen_name = $verification['screen_name'];
        $config->twitter_id = $verification['id_str'];
        $config->linked_twitter = 1;
        $config->save();
    }
}
