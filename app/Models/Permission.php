<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    // ─── camelCase timestamp columns ─────────────────────────────────────────
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    // Permissions are internal config — no uuid, no soft deletes
    protected $fillable = [
        'name',
        'group',
        'label',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * All roles that have this permission.
     * Pivot FK: permissionId → permissions.id (integer FK — config table)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permissionId', // FK in pivot → permissions.id
            'roleId',       // FK in pivot → roles.id
        )->withTimestamps('createdAt', 'updatedAt');
    }
}
