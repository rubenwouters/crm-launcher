<?php

namespace Rubenwouters\CrmLauncher\Traits;

Trait CrmLauncherUserTrait
{
    public function role()
    {
        return $this->belongsTo('Rubenwouters\CrmLauncher\Models\Role');
    }

    public function cases()
    {
        return $this->belongsToMany('Rubenwouters\CrmLauncher\Models\CaseOverview', 'users_cases', 'user_id', 'case_id');
    }

    public function answers()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Answer');
    }

    public function summaries()
    {
        return $this->hasMany('Rubenwouters\CrmLauncher\Models\Summary');
    }
}
