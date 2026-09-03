<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->id();

        if ($tenantId === null || $tenantId === '') {
            // Fail closed. A tenant-scoped model must never become a global
            // query merely because tenant context was not established.
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $tenantId);
    }
}
