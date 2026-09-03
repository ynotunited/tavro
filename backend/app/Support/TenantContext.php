<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use LogicException;

final class TenantContext
{
    private const KEY = 'tavro.tenant.organization_id';

    public function set(int|string $organizationId): void
    {
        app()->instance(self::KEY, (string) $organizationId);
    }

    public function clear(): void
    {
        app()->forgetInstance(self::KEY);
    }

    public function id(): ?string
    {
        return app()->bound(self::KEY) ? (string) app(self::KEY) : null;
    }

    public function requiredId(): string
    {
        $id = $this->id();
        if ($id === null || $id === '') {
            throw new LogicException('A tenant context is required for this operation.');
        }
        return $id;
    }

    public function run(int|string $organizationId, Closure $callback): mixed
    {
        $previous = $this->id();
        $this->set($organizationId);
        try {
            return $callback();
        } finally {
            $previous === null ? $this->clear() : $this->set($previous);
        }
    }
}
