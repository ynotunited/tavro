<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesByTenant
{
    protected static function bootScopesByTenant()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_org_id')) {
                $builder->where('organization_id', app('current_org_id'));
            }
        });
    }
}
