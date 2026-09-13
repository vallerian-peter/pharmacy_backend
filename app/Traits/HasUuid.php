<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Auto-generates a UUID when a model is created.
 *
 * Rules:
 *   - uuid is generated locally (offline-safe) before the record hits the server
 *   - uuid is used as the FK reference between offline-created tables
 *   - id (integer) is the database primary key — used for internal joins only
 *   - Routes use uuid as the route key (never expose integer id in URLs)
 */
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Use uuid in route model binding: /api/users/{uuid}
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
