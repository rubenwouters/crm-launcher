<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Log extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'logs';

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Relationships of Log model
    |
    */

    public function scopeLatestLog($query, $type)
    {
        return $query->where('case_type', $type)->orderBy('id', 'DESC')->first()->created_at;
    }

    public function scopeDashboardUpdate($query)
    {
        return $query->where('case_type', DASHBOARD_UPDATE);
    }

    /**
     * Updates logs to keep track of API rate limits
     *
     * @param  string $type
     *
     * @return void
     */
    public function updateLog($type)
    {
        $log = new Log();
        if ($type == FETCHING) {
            $log->case_type = FETCHING;
        } else if ($type == DASHBOARD_UPDATE) {
            $log->case_type = DASHBOARD_UPDATE;
        } else if ($type == STATS) {
            $log->case_type = STATS;
        } else if ($type == PUBLISHMENTS) {
            $log->case_type = PUBLISHMENTS;
        } else if ($type == PUBLISHMENT_DETAIL) {
            $log->case_type = PUBLISHMENT_DETAIL;
        }

        $log->save();
    }

    /**
     * Check seconds between previous API call & now
     * @return int
     */
    public function secondsago($type)
    {
        if (Log::where('case_type', $type)->exists()) {
            $now = Carbon::now();
            $last = Log::LatestLog($type);
            $lastLog = new Carbon($last);

            return $now->diffInSeconds($lastLog);
        }
        return false;
    }
}
