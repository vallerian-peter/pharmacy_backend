<?php

namespace App\Models;

use App\Enum\StatusEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasUuid, SoftDeletes;

    // ─── camelCase timestamp columns ─────────────────────────────────────────
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    protected $fillable = [
        'uuid',
        'name',
        'address',
        'phone',
        'invoicePrefix',
        'isMainBranch',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'isMainBranch' => 'boolean',
            'status'       => StatusEnum::class,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === StatusEnum::ACTIVE;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * All staff assignments at this branch.
     * FK: user_branch_roles.branchUuid → branches.uuid
     */
    public function userBranchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'branchUuid', 'uuid');
    }

    /**
     * Users working at this branch (via pivot).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_branch_roles',
            'branchUuid',  // FK in pivot pointing to this model (branches.uuid)
            'userUuid',    // FK in pivot pointing to related model (users.uuid)
            'uuid',        // local key on branches
            'uuid',        // local key on users
        )->withPivot(['roleId', 'status'])
         ->withTimestamps('createdAt', 'updatedAt');
    }
}
