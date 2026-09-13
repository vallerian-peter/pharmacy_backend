<?php

namespace App\Enum;

enum RoleEnum: string
{
    case SUPER_ADMIN    = 'Super Admin';    // System-level account (superadmin user)
    case OWNER          = 'Owner';          // Pharmacy business owner
    case BRANCH_MANAGER = 'Branch Manager';
    case PHARMACIST     = 'Pharmacist';
    case CASHIER        = 'Cashier';

    public function label(): string
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }

    public static function toArrayForSelect(): array
    {
        return array_map(fn($case) => $case->toArray(), self::cases());
    }

    /**
     * Roles that are scoped to a specific branch.
     * SUPER_ADMIN and OWNER have branchUuid = NULL (all branches).
     */
    public function isBranchScoped(): bool
    {
        return $this !== self::OWNER && $this !== self::SUPER_ADMIN;
    }
}
