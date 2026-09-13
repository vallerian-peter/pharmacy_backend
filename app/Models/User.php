<?php

namespace App\Models;

use App\Enum\StatusEnum;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuid, Notifiable, SoftDeletes;

    // ─── camelCase timestamp columns ─────────────────────────────────────────
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    // ─── camelCase remember token ─────────────────────────────────────────────
    protected $rememberTokenName = 'rememberToken';

    protected $fillable = [
        'uuid',
        'name',
        'username',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'rememberToken',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status'   => StatusEnum::class,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === StatusEnum::ACTIVE;
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * All branch+role assignments for this user.
     * FK: user_branch_roles.userUuid → users.uuid
     */
    public function branchRoles(): HasMany
    {
        return $this->hasMany(UserBranchRole::class, 'userUuid', 'uuid');
    }

    /**
     * Branches this user is assigned to (via pivot).
     * Pivot FK: userUuid → uuid, branchUuid → uuid
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'user_branch_roles',
            'userUuid',    // FK in pivot pointing to this model (users.uuid)
            'branchUuid',  // FK in pivot pointing to related model (branches.uuid)
            'uuid',        // local key on users
            'uuid',        // local key on branches
        )->withPivot(['roleId', 'status'])
         ->withTimestamps('createdAt', 'updatedAt');
    }

    /**
     * Whether this user is an Owner (branchUuid = NULL assignment).
     */
    public function isOwner(): bool
    {
        return $this->branchRoles()
                    ->whereNull('branchUuid')
                    ->where('status', 'Active')
                    ->exists();
    }

    /**
     * Get the user's role at a specific branch (by branch uuid).
     */
    public function roleAtBranch(string $branchUuid): ?Role
    {
        $assignment = $this->branchRoles()
                           ->where('branchUuid', $branchUuid)
                           ->where('status', 'Active')
                           ->with('role')
                           ->first();

        return $assignment?->role;
    }

    /**
     * Get the active assigned branch for this user.
     */
    public function assignedBranch(): ?Branch
    {
        $assignment = $this->branchRoles()
                           ->whereNotNull('branchUuid')
                           ->where('status', 'Active')
                           ->whereNull('deletedAt')
                           ->with('branch')
                           ->first();

        if ($assignment?->branch) {
            return $assignment->branch;
        }

        // For owner without a specific branch assignment, fallback to main branch if available
        if ($this->isOwner()) {
            return Branch::where('isMainBranch', true)->first() ?? Branch::first();
        }

        return null;
    }
}
