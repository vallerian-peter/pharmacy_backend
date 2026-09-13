<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    // ─── camelCase timestamp columns ─────────────────────────────────────────
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    // Roles are internal config — no uuid, no soft deletes
    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * All permissions this role has.
     * Pivot FK: roleId → roles.id (integer FK — config table)
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'roleId',       // FK in pivot → roles.id
            'permissionId', // FK in pivot → permissions.id
        )->withTimestamps('createdAt', 'updatedAt');
    }

    /**
     * All user branch assignments using this role.
     * FK: user_branch_roles.roleId → roles.id (integer FK)
     */
    public function userBranchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'roleId', 'id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }
}
