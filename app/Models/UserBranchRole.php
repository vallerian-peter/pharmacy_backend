<?php

namespace App\Models;

use App\Enum\UserBranchRoleStatusEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBranchRole extends Model
{
    use HasUuid, SoftDeletes;

    protected $table = 'user_branch_roles';

    // ─── camelCase timestamp columns ─────────────────────────────────────────
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    protected $fillable = [
        'uuid',
        'userUuid',    // → users.uuid (uuid FK)
        'branchUuid',  // → branches.uuid (uuid FK); NULL = Owner
        'roleId',      // → roles.id (integer FK — config table)
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => UserBranchRoleStatusEnum::class,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === UserBranchRoleStatusEnum::ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserBranchRoleStatusEnum::SUSPENDED;
    }

    /**
     * Owner assignments have no branch (branchUuid = NULL).
     */
    public function isOwnerAssignment(): bool
    {
        return is_null($this->branchUuid);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * The user this assignment belongs to.
     * FK: userUuid → users.uuid  (uuid FK)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userUuid', 'uuid');
    }

    /**
     * The branch this assignment belongs to.
     * FK: branchUuid → branches.uuid  (uuid FK, nullable = Owner)
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branchUuid', 'uuid');
    }

    /**
     * The role for this assignment.
     * FK: roleId → roles.id  (integer FK — roles are config, no uuid)
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'roleId', 'id');
    }
}
