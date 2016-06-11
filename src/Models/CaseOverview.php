<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class CaseOverview extends Model
{
    const ITEM_PER_PAGE = 12;

    /**
     * table name
     * @var string
     */
    protected $table = 'cases';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    |
    | Relationships of CaseOvervie model
    |
    */

    public function users()
    {
        return $this->belongsToMany('App\User', 'users_cases', 'case_id', 'user_id');
    }

    public function messages()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Message', 'case_id');
    }

    public function innerComment()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerComment', 'case_id');
    }

    public function innerAnswers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\InnerAnswer');
    }

    public function summaries()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Summary', 'case_id');
    }

    public function contact()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Contact');
    }

    public function answers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Answer');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes of CaseOverview model
    |
    */
    public function scopeAllCases($query)
    {
        return $query->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->paginate(self::ITEM_PER_PAGE);
    }

    public function scopeVisibleCases($query)
    {
        return $query->orderBy('updated_at', 'DESC')->where('status', '!=', '2')->orderBy('id', 'DESC')->paginate(self::ITEM_PER_PAGE);
    }

    public function scopePrivateFbMessages($query, $contact)
    {
        return $query->where('origin', 'Facebook private')->where('contact_id', $contact->id);
    }

    public function scopeNewCases($query)
    {
        return $query->where('status', '0')->orderBy('id', 'DESC')->get();
    }

    public function scopeOpenCases($query)
    {
        return $query->where('status', '1')->orderBy('id', 'DESC')->get();
    }

    public function scopeClosedCases($query)
    {
        return $query->where('status', '2')->orderBy('id', 'DESC')->get();
    }
    
    public function scopePendingCases($query)
    {
        return $query->where('status', '1')->orWhere('status', '2')->get();
    }

    /**
     * Inserts new case in DB
     * @param  string $type
     * @param  array $message
     * @param  object $contact
     * @return object
     */
    public function createCase($type, $message, $contact)
    {
        $case = new CaseOverview();
        $case->contact_id = $contact->id;

        if ($type == TWITTER_MENTION) {
            $case->origin = 'Twitter mention';
        } else if ($type == TWITTER_DIRECT) {
            $case->origin = 'Twitter direct';
        } else if ($type == FACEBOOK_POST) {
            $case->origin = 'Facebook post';
        } else if ($type == FACEBOOK_PRIVATE) {
            $case->origin = 'Facebook private';
        }

        $case->status = 0;
        $case->save();

        return $case;
    }

    /**
     * Changes status of case to "open"
     * @param  object $case
     * @return void
     */
    public function openCase($case)
    {
        $case->status = 1;
        $case->save();
    }
}
