<?php

namespace App\Enum;

enum StatusEnum: string
{
    case ACTIVE = 'Active';
    case INACTIVE = 'Inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
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
        return [
            self::ACTIVE->toArray(),
            self::INACTIVE->toArray(),
        ];
    }
}
