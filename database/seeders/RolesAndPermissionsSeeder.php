<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
use App\Enum\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed roles, permissions, and the role→permission matrix.
     *
     * This is the authoritative source of access control for the system.
     * Do NOT add a UI for users to modify this — roles are fixed by design.
     */
    public function run(): void
    {
        // ── 1. Seed Roles ────────────────────────────────────────────────────
        $roles = [];
        foreach ($this->roleDefinitions() as $def) {
            $roles[$def['name']] = Role::firstOrCreate(
                ['name' => $def['name']],
                ['label' => $def['label'], 'description' => $def['description']],
            );
        }

        // ── 2. Seed Permissions ───────────────────────────────────────────────
        $permissions = [];
        foreach (PermissionEnum::cases() as $perm) {
            $permissions[$perm->value] = Permission::firstOrCreate(
                ['name' => $perm->value],
                ['group' => $perm->group(), 'label' => $perm->label()],
            );
        }

        // ── 3. Assign Permissions to Roles (the matrix) ───────────────────────
        foreach ($this->permissionMatrix() as $roleName => $permissionNames) {
            $role = $roles[$roleName];

            // Map permission names → permission ids
            // (role_permissions pivot uses integer FKs: roleId, permissionId)
            $permissionIds = collect($permissionNames)
                ->map(fn($name) => $permissions[$name]->id)
                ->all();

            // sync() replaces existing assignments — idempotent on re-seed
            $role->permissions()->sync($permissionIds);
        }

        $this->command->info('✅ Roles, Permissions, and Role-Permission matrix seeded.');
    }

    // ─── Role Definitions ─────────────────────────────────────────────────────

    private function roleDefinitions(): array
    {
        return [
            [
                'name'        => RoleEnum::SUPER_ADMIN->value,
                'label'       => 'Super Admin',
                'description' => 'System-level administrator. Full unrestricted access across all branches and settings.',
            ],
            [
                'name'        => RoleEnum::OWNER->value,
                'label'       => 'Owner',
                'description' => 'Full access to all branches. No branch scoping. System administrator.',
            ],
            [
                'name'        => RoleEnum::BRANCH_MANAGER->value,
                'label'       => 'Branch Manager',
                'description' => 'Full operational access, scoped to their assigned branch(es).',
            ],
            [
                'name'        => RoleEnum::PHARMACIST->value,
                'label'       => 'Pharmacist',
                'description' => 'Clinical, sales, and inventory duties, scoped to their branch.',
            ],
            [
                'name'        => RoleEnum::CASHIER->value,
                'label'       => 'Cashier',
                'description' => 'POS and payments only, scoped to their branch.',
            ],
        ];
    }

    // ─── Permission Matrix ────────────────────────────────────────────────────
    // Source of truth: pharmacy-system-technical-implementation.md §4.4
    //
    //  ✓ = granted   ✗ = not granted
    //
    //  Permission                     | Cashier | Pharmacist | Manager | Owner
    //  -------------------------------|---------|------------|---------|------
    //  POS_CREATE / POS_VIEW          |   ✓     |     ✓      |    ✓    |   ✓
    //  POS_CANCEL                     |   ✗     |     ✓      |    ✓    |   ✓
    //  PAYMENT_CREATE / VIEW          |   ✓     |     ✓      |    ✓    |   ✓
    //  PRODUCT_VIEW                   |   ✓     |     ✓      |    ✓    |   ✓
    //  PRODUCT_CREATE / UPDATE        |   ✗     |     ✓      |    ✓    |   ✓
    //  PRODUCT_DELETE                 |   ✗     |     ✗      |    ✗    |   ✓
    //  INVENTORY_VIEW/CREATE/ADJUST   |   ✗     |     ✓      |    ✓    |   ✓
    //  CUSTOMER_VIEW / CREATE         |   ✓     |     ✓      |    ✓    |   ✓
    //  SUPPLIER_VIEW / CREATE         |   ✗     |     ✗      |    ✓    |   ✓
    //  REPORT_VIEW (own branch)       |   ✗     |     ✗      |    ✓    |   ✓
    //  REPORT_EXPORT                  |   ✗     |     ✗      |    ✓    |   ✓
    //  USER_VIEW (own branch)         |   ✗     |     ✗      |    ✓    |   ✓
    //  USER_CREATE/UPDATE/DELETE      |   ✗     |     ✗      |    ✗    |   ✓
    //  BRANCH_MANAGE                  |   ✗     |     ✗      |    ✗    |   ✓
    //  SETTINGS_MANAGE                |   ✗     |     ✗      |    ✗    |   ✓

    private function permissionMatrix(): array
    {
        $p = PermissionEnum::class;

        return [
            RoleEnum::CASHIER->value => [
                PermissionEnum::POS_CREATE->value,
                PermissionEnum::POS_VIEW->value,
                PermissionEnum::PAYMENT_CREATE->value,
                PermissionEnum::PAYMENT_VIEW->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
            ],

            RoleEnum::PHARMACIST->value => [
                PermissionEnum::POS_CREATE->value,
                PermissionEnum::POS_VIEW->value,
                PermissionEnum::POS_CANCEL->value,
                PermissionEnum::PAYMENT_CREATE->value,
                PermissionEnum::PAYMENT_VIEW->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_CREATE->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_CREATE->value,
                PermissionEnum::INVENTORY_ADJUST->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
            ],

            RoleEnum::BRANCH_MANAGER->value => [
                PermissionEnum::POS_CREATE->value,
                PermissionEnum::POS_VIEW->value,
                PermissionEnum::POS_CANCEL->value,
                PermissionEnum::PAYMENT_CREATE->value,
                PermissionEnum::PAYMENT_VIEW->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_CREATE->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_CREATE->value,
                PermissionEnum::INVENTORY_ADJUST->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::SUPPLIER_VIEW->value,
                PermissionEnum::SUPPLIER_CREATE->value,
                PermissionEnum::REPORT_VIEW->value,
                PermissionEnum::REPORT_EXPORT->value,
                PermissionEnum::USER_VIEW->value,
            ],

            RoleEnum::SUPER_ADMIN->value => [
                // ─── Users ───────────────────────────────
                PermissionEnum::USER_VIEW->value,
                PermissionEnum::USER_CREATE->value,
                PermissionEnum::USER_UPDATE->value,
                PermissionEnum::USER_DELETE->value,

                // ─── Products ─────────────────────────────
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_CREATE->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::PRODUCT_DELETE->value,

                // ─── Inventory ────────────────────────────
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_CREATE->value,
                PermissionEnum::INVENTORY_ADJUST->value,

                // ─── POS ──────────────────────────────────
                PermissionEnum::POS_CREATE->value,
                PermissionEnum::POS_VIEW->value,
                PermissionEnum::POS_CANCEL->value,

                // ─── Payments ─────────────────────────────
                PermissionEnum::PAYMENT_CREATE->value,
                PermissionEnum::PAYMENT_VIEW->value,

                // ─── People ───────────────────────────────
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::SUPPLIER_VIEW->value,
                PermissionEnum::SUPPLIER_CREATE->value,

                // ─── Reports ──────────────────────────────
                PermissionEnum::REPORT_VIEW->value,
                PermissionEnum::REPORT_EXPORT->value,

                // ─── Branches & Settings ──────────────────
                PermissionEnum::BRANCH_MANAGE->value,
                PermissionEnum::SETTINGS_MANAGE->value,
            ],

            RoleEnum::OWNER->value => [
                // ─── Users ───────────────────────────────
                PermissionEnum::USER_VIEW->value,
                PermissionEnum::USER_CREATE->value,
                PermissionEnum::USER_UPDATE->value,
                PermissionEnum::USER_DELETE->value,       // ← Owner only

                // ─── Products ─────────────────────────────
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_CREATE->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::PRODUCT_DELETE->value,    // ← Owner only

                // ─── Inventory ────────────────────────────
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_CREATE->value,
                PermissionEnum::INVENTORY_ADJUST->value,

                // ─── POS ──────────────────────────────────
                PermissionEnum::POS_CREATE->value,
                PermissionEnum::POS_VIEW->value,
                PermissionEnum::POS_CANCEL->value,

                // ─── Payments ─────────────────────────────
                PermissionEnum::PAYMENT_CREATE->value,
                PermissionEnum::PAYMENT_VIEW->value,

                // ─── People ───────────────────────────────
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::SUPPLIER_VIEW->value,
                PermissionEnum::SUPPLIER_CREATE->value,

                // ─── Reports ──────────────────────────────
                PermissionEnum::REPORT_VIEW->value,
                PermissionEnum::REPORT_EXPORT->value,

                // ─── Branches & Settings ──────────────────
                PermissionEnum::BRANCH_MANAGE->value,     // ← Owner only
                PermissionEnum::SETTINGS_MANAGE->value,   // ← Owner only
            ],
        ];
    }
}
