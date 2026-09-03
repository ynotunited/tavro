<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null || $tenantId === '') {
                return;
            }

            $modelTenantId = $model->getAttribute('organization_id');

            if ($modelTenantId === null) {
                $model->setAttribute('organization_id', $tenantId);
                return;
            }

            if ((string) $modelTenantId !== $tenantId) {
                throw new LogicException('The model organization does not match the current tenant context.');
            }
        });

        static::saving(function (Model $model): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null || $tenantId === '') {
                return;
            }

            $modelTenantId = $model->getAttribute('organization_id');

            if ($modelTenantId !== null && (string) $modelTenantId !== $tenantId) {
                throw new LogicException('The model organization does not match the current tenant context.');
            }
        });
    }
}
