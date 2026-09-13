<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use App\Enum\StatusEnum;
use App\Enum\UserBranchRoleStatusEnum;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBranchRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    /**
     * Seed the two privileged system accounts.
     *
     * | Username   | Role        | branchUuid | Notes                         |
     * |------------|-------------|------------|-------------------------------|
     * | superadmin | SUPER_ADMIN | NULL       | System-level — top of all     |
     * | admin      | OWNER       | NULL       | Business owner — full access  |
     *
     * Uses firstOrCreate so re-running is idempotent.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name'     => 'Super Admin',
                'username' => 'superadmin',
                'email'    => 'superadmin@pharmacy.local',
                'password' => 'testtest',
                'roleEnum' => RoleEnum::SUPER_ADMIN,
            ],
            [
                'name'     => 'Admin',
                'username' => 'admin',
                'email'    => 'admin@pharmacy.local',
                'password' => 'testtest',
                'roleEnum' => RoleEnum::OWNER,
            ],
        ];

        foreach ($accounts as $account) {
            /** @var RoleEnum $roleEnum */
            $roleEnum = $account['roleEnum'];

            // ── Resolve role from DB ─────────────────────────────────────────────
            $role = Role::where('name', $roleEnum->value)->firstOrFail();

            // ── 1. Create or retrieve the User ──────────────────────────────────
            $user = User::firstOrCreate(
                ['username' => $account['username']],
                [
                    'name'     => $account['name'],
                    'email'    => $account['email'],
                    'password' => Hash::make($account['password']),
                    'status'   => StatusEnum::ACTIVE->value,
                ],
            );

            // ── 2. Clear any old NULL-branch assignment then assign correct role ─
            // Delete the existing global assignment so we can re-assign properly
            // (handles case where user was previously seeded with wrong role).
            UserBranchRole::where('userUuid', $user->uuid)
                           ->whereNull('branchUuid')
                           ->delete();

            UserBranchRole::create([
                'userUuid'   => $user->uuid,
                'branchUuid' => null,
                'roleId'     => $role->id,
                'status'     => UserBranchRoleStatusEnum::ACTIVE->value,
            ]);

            $this->command->info("✅ Seeded: {$account['username']} → {$role->name}");
        }
    }
}
