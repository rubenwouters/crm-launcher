<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{
    /**
     * table name
     * @var string
     */
    protected $table = 'summaries';

    // DB relationships
    public function caseOverview(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Case');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }
}
