<?php

namespace App\Services;

use App\Enum\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Check if a user has a given permission, optionally scoped to a branch uuid.
     *
     * Usage:
     *   PermissionService::can($user, 'POS_CANCEL', $branchUuid)
     *   PermissionService::can($user, 'BRANCH_MANAGE')   // Owner-only — no branch needed
     *
     * Rules:
     *   - OWNER (branchUuid = NULL in user_branch_roles) → always passes regardless of $branchUuid
     *   - Others → must have an active assignment at $branchUuid with a role that has the permission
     */
    public static function can(User $user, string $permission, ?string $branchUuid = null): bool
    {
        // Owner bypass — never branch-scoped
        if (static::isOwner($user)) {
            return true;
        }

        // Branch-scoped users must provide a branch context
        if ($branchUuid === null) {
            return false;
        }

        $role = static::getRoleAtBranch($user, $branchUuid);

        if ($role === null) {
            return false;
        }

        return static::roleHasPermission($role, $permission);
    }

    /**
     * Throw a 403 if the user does NOT have the permission.
     */
    public static function authorize(User $user, string $permission, ?string $branchUuid = null): void
    {
        if (! static::can($user, $permission, $branchUuid)) {
            abort(403, "Unauthorized: missing permission [{$permission}].");
        }
    }

    /**
     * Get all permission names for a user at a specific branch uuid.
     * Returns all permissions for Owner (no branch scoping).
     */
    public static function permissionsFor(User $user, ?string $branchUuid = null): array
    {
        if (static::isOwner($user)) {
            return Cache::remember('permissions_all', 3600, function () {
                return \App\Models\Permission::pluck('name')->all();
            });
        }

        if ($branchUuid === null) {
            return [];
        }

        $role = static::getRoleAtBranch($user, $branchUuid);

        if ($role === null) {
            return [];
        }

        return Cache::remember("permissions_role_{$role->id}", 3600, function () use ($role) {
            return $role->permissions()->pluck('name')->all();
        });
    }

    /**
     * Return the role KEY (e.g. "SUPER_ADMIN", "OWNER", "BRANCH_MANAGER") for a user.
     *
     * The frontend RoleType contract uses enum KEYS (UPPERCASE).
     * For global roles (branchUuid=NULL), we fetch the actual role assigned
     * so SUPER_ADMIN and OWNER are correctly distinguished.
     */
    public static function roleNameFor(User $user, ?string $branchUuid = null): ?string
    {
        if (static::isOwner($user)) {
            // Fetch the actual role from the NULL-branch assignment to distinguish
            // SUPER_ADMIN from OWNER (both have branchUuid = NULL).
            $globalAssignment = $user->branchRoles()
                                     ->whereNull('branchUuid')
                                     ->where('status', 'Active')
                                     ->with('role')
                                     ->first();

            $roleName = $globalAssignment?->role?->name;

            if ($roleName === null) {
                return RoleEnum::OWNER->name; // fallback
            }

            // Map stored label ("Super Admin") → enum key ("SUPER_ADMIN")
            $case = RoleEnum::tryFrom($roleName);
            return $case?->name ?? strtoupper(str_replace(' ', '_', $roleName));
        }

        if ($branchUuid === null) {
            return null;
        }

        // Branch-scoped: map label → enum key
        $roleName = static::getRoleAtBranch($user, $branchUuid)?->name;

        if ($roleName === null) {
            return null;
        }

        $case = RoleEnum::tryFrom($roleName);
        return $case?->name ?? $roleName;
    }


    // ─── Private Helpers ─────────────────────────────────────────────────────

    private static function isOwner(User $user): bool
    {
        // Cache key uses user id (internal) — never expose in response
        // Both OWNER and SUPER_ADMIN rows have NULL branchUuid → global access
        return Cache::remember("user_{$user->id}_is_owner", 3600, function () use ($user) {
            return $user->branchRoles()
                        ->whereNull('branchUuid')   // NULL branchUuid = global role
                        ->where('status', 'Active')
                        ->exists();
        });
    }

    private static function getRoleAtBranch(User $user, string $branchUuid): ?Role
    {
        return Cache::remember("user_{$user->id}_role_branch_{$branchUuid}", 3600, function () use ($user, $branchUuid) {
            $assignment = $user->branchRoles()
                               ->where('branchUuid', $branchUuid)  // camelCase column
                               ->where('status', 'Active')
                               ->whereNull('deletedAt')             // camelCase soft delete column
                               ->with(['role.permissions'])
                               ->first();

            return $assignment?->role;
        });
    }

    private static function roleHasPermission(Role $role, string $permission): bool
    {
        return $role->permissions->contains('name', $permission);
    }

    /**
     * Clear cached permissions for a user when their role changes.
     */
    public static function clearCache(User $user, ?string $branchUuid = null): void
    {
        Cache::forget("user_{$user->id}_is_owner");

        if ($branchUuid) {
            Cache::forget("user_{$user->id}_role_branch_{$branchUuid}");
        }
    }
}
