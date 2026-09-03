<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'organization_id', 'reported_by', 'title', 'description',
        'category', 'severity', 'status', 'metadata',
        'resolved_at', 'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata'    => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Auto-create an issue from an exception or error context.
     */
    public static function reportFromException(
        \Throwable $exception,
        ?int $organizationId = null,
        ?int $userId = null,
        array $extra = [],
    ): static {
        return static::create([
            'organization_id' => $organizationId,
            'reported_by'     => $userId,
            'title'           => get_class($exception) . ': ' . $exception->getMessage(),
            'description'     => $exception->getTraceAsString(),
            'category'        => 'bug',
            'severity'        => match (true) {
                $exception instanceof \ErrorException,
                $exception instanceof \PDOException => 'critical',
                $exception instanceof \InvalidArgumentException => 'low',
                default => 'medium',
            },
            'status'          => 'open',
            'metadata'        => array_merge([
                'file'      => $exception->getFile(),
                'line'      => $exception->getCode(),
                'exception' => get_class($exception),
            ], $extra),
        ]);
    }
}
