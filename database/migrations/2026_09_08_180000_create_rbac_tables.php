<?php

use App\Enum\UserBranchRoleStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ══════════════════════════════════════════════════════════════════
 * FK STRATEGY used in this file:
 *
 *  roleId      → roles.id       (INTEGER FK)
 *               Roles are server-side config, never created offline.
 *               Integer FK is safe.
 *
 *  userUuid    → users.uuid     (UUID / STRING FK)
 *  branchUuid  → branches.uuid  (UUID / STRING FK, nullable for Owner)
 *               Users and branches could be referenced offline before
 *               server assigns their integer id.
 *               UUID FK is the safe choice.
 *
 * permissionId → permissions.id (INTEGER FK)
 *               Permissions are server-side config, same as roles.
 * ══════════════════════════════════════════════════════════════════
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── roles ────────────────────────────────────────────────────────────
        // No uuid: config table, server-side only. Referenced by roleId (integer).
        Schema::create('roles', function (Blueprint $table) {
            $table->id();                               // PK — referenced as roleId elsewhere

            $table->string('name')->unique();           // 'Owner', 'Branch Manager', etc.
            $table->string('label');
            $table->text('description')->nullable();

            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });

        // ─── permissions ──────────────────────────────────────────────────────
        // No uuid: config table, server-side only. Referenced by permissionId (integer).
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();                               // PK — referenced as permissionId elsewhere

            $table->string('name')->unique();           // 'POS_CANCEL', 'PRODUCT_DELETE', etc.
            $table->string('group');                    // 'POS', 'PRODUCTS', 'BRANCHES', etc.
            $table->string('label');

            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });

        // ─── role_permissions ─────────────────────────────────────────────────
        // Pure pivot — server-side config. Both FKs are integer (config tables).
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('roleId');       // → roles.id (integer FK)
            $table->foreign('roleId')
                  ->references('id')->on('roles')
                  ->cascadeOnDelete();

            $table->unsignedBigInteger('permissionId'); // → permissions.id (integer FK)
            $table->foreign('permissionId')
                  ->references('id')->on('permissions')
                  ->cascadeOnDelete();

            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            $table->unique(['roleId', 'permissionId']); // a role cannot have the same permission twice
        });

        // ─── user_branch_roles ────────────────────────────────────────────────
        // This IS exposed via API (for managing staff assignments).
        // Has uuid as public identifier.
        // FKs:
        //   userUuid   → users.uuid   (uuid FK — user could be referenced offline)
        //   branchUuid → branches.uuid (uuid FK — branch referenced offline; NULL = Owner)
        //   roleId     → roles.id     (integer FK — roles are config, always server-side)
        Schema::create('user_branch_roles', function (Blueprint $table) {
            $table->id();                                       // integer PK — internal only
            $table->string('uuid')->unique();                  // PUBLIC id — used in API/URLs

            $table->string('userUuid');                        // → users.uuid (uuid FK)
            $table->foreign('userUuid')
                  ->references('uuid')->on('users')
                  ->cascadeOnDelete();

            $table->string('branchUuid')->nullable();          // → branches.uuid (uuid FK); NULL = Owner
            $table->foreign('branchUuid')
                  ->references('uuid')->on('branches')
                  ->nullOnDelete();

            $table->unsignedBigInteger('roleId');              // → roles.id (integer FK)
            $table->foreign('roleId')
                  ->references('id')->on('roles')
                  ->cascadeOnDelete();

            $table->string('status')
                  ->default(UserBranchRoleStatusEnum::ACTIVE->value); // 'Active' or 'Suspended'

            $table->softDeletes('deletedAt');
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();

            // Fast lookup: "What is this user's role at branch X?"
            $table->index(['userUuid', 'branchUuid']);
            $table->index('branchUuid');
        });

        // Partial unique indexes for NULL-safe uniqueness in PostgreSQL:
        //   A user can only have ONE Owner assignment (userUuid UNIQUE where branchUuid IS NULL)
        //   A user can only have ONE role per branch (userUuid + branchUuid UNIQUE where branchUuid IS NOT NULL)
        DB::statement(
            'CREATE UNIQUE INDEX ubr_owner_unique
             ON user_branch_roles ("userUuid")
             WHERE "branchUuid" IS NULL'
        );
        DB::statement(
            'CREATE UNIQUE INDEX ubr_branch_unique
             ON user_branch_roles ("userUuid", "branchUuid")
             WHERE "branchUuid" IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
