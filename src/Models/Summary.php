<?php

namespace Rubenwouters\CrmLauncher\Models;

use Illuminate\Database\Eloquent\Model;

class Summary extends Model
{

    // DB relationships
    public function case(){
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Case');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }
}
