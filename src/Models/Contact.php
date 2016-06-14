<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'contacts';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of Contact model
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes of Contact model
    |
    */

    public function scopeFindByFbId($query, $id)
    {
        return $query->where('facebook_id', $id)->orderBy('id', 'DESC')->first();
    }

    public function scopeFindByTwitterId($query, $id)
    {
        return $query->where('twitter_id', $id)->first();
    }

    /**
     * Inserts new contact in DB
     * @param  string $type
     * @param  array $message
     * @return object
     */
    public function createContact($type, $message)
    {
        if ($type == "twitter_mention") {

            $contact = $this->getContact('twitter', $message['user']['id_str']);
            $contact->name = $message['user']['name'];
            $contact->twitter_handle = $message['user']['screen_name'];
            $contact->twitter_id = $message['user']['id_str'];
            $contact->profile_picture = $message['user']['profile_image_url'];

        } else if ($type == "twitter_direct") {

            $contact = $this->getContact('twitter', $message['sender']['id_str']);
            $contact->name = $message['sender']['name'];
            $contact->twitter_handle = $message['sender']['screen_name'];
            $contact->twitter_id = $message['sender']['id_str'];
            $contact->profile_picture = $message['sender']['profile_image_url'];

        } else if ($type == "facebook") {

            $contact = $this->getContact('facebook', $message->from->id);
            $contact->name = $message->from->name;
            $contact->facebook_id = $message->from->id;
            $contact->profile_picture = getProfilePicture($message->from->id);
        }

        $contact->save();

        return $contact;
    }

    /**
     * Check if contact exists, if not create a new user
     * @param  string  $type
     * @param  string  $id
     * @return collection
     */
    public function getContact($type, $id)
    {
        if ($type == 'twitter' && Contact::where('twitter_id', $id)->exists()) {

            return Contact::findByTwitterId($id);
        } else if ($type == 'facebook' && Contact::where('facebook_id', $id)->exists()) {

            return Contact::findByFbId($id);
        }

        return new Contact();
    }

}
