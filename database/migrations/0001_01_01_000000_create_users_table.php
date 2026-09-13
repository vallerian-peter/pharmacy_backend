<?php

use App\Enum\StatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ─────────────────────────────────────────────────────
 * Column naming convention (ALL tables in this project)
 * ─────────────────────────────────────────────────────
 *  ✅ camelCase  : userName, isMainBranch, invoicePrefix
 *  ❌ snake_case : user_name, is_main_branch
 *
 * FK naming convention:
 *  - Config/seed tables (roles, permissions) → integer id FK : roleId, permissionId
 *  - Operational/offline tables             → uuid FK       : userUuid, branchUuid
 * ─────────────────────────────────────────────────────
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── users ───────────────────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                       // integer PK — internal joins only
            $table->string('uuid')->unique();                  // PUBLIC id — used as FK in offline tables

            $table->string('name');
            $table->string('username')->unique();              // Primary login identifier
            $table->string('email')->nullable()->unique();     // Optional
            $table->string('phone')->nullable();

            $table->string('password');
            $table->string('status')->default(StatusEnum::ACTIVE->value);

            $table->string('rememberToken', 100)->nullable();  // camelCase (overrides Laravel default)
            $table->softDeletes('deletedAt');                  // camelCase
            $table->timestamp('createdAt')->nullable();        // camelCase
            $table->timestamp('updatedAt')->nullable();        // camelCase
        });

        // ─── sessions ─────────────────────────────────────────────────────────
        // Sessions are server-side only (auth). Kept in Laravel standard format
        // because the session driver reads these column names from config.
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index(); // server-side auth — integer FK ok
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
