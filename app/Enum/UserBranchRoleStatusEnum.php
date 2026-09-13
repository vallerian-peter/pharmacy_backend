<?php

namespace App\Enum;

enum UserBranchRoleStatusEnum: string
{
    case ACTIVE    = 'Active';
    case SUSPENDED = 'Suspended';

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
}
