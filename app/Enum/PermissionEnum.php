<?php

namespace App\Enum;

enum PermissionEnum: string
{
    // Users
    case USER_VIEW   = 'USER_VIEW';
    case USER_CREATE = 'USER_CREATE';
    case USER_UPDATE = 'USER_UPDATE';
    case USER_DELETE = 'USER_DELETE';

    // Products
    case PRODUCT_VIEW   = 'PRODUCT_VIEW';
    case PRODUCT_CREATE = 'PRODUCT_CREATE';
    case PRODUCT_UPDATE = 'PRODUCT_UPDATE';
    case PRODUCT_DELETE = 'PRODUCT_DELETE';

    // Inventory
    case INVENTORY_VIEW   = 'INVENTORY_VIEW';
    case INVENTORY_CREATE = 'INVENTORY_CREATE';
    case INVENTORY_ADJUST = 'INVENTORY_ADJUST';

    // POS / Sales
    case POS_CREATE = 'POS_CREATE';
    case POS_VIEW   = 'POS_VIEW';
    case POS_CANCEL = 'POS_CANCEL';

    // Payments
    case PAYMENT_CREATE = 'PAYMENT_CREATE';
    case PAYMENT_VIEW   = 'PAYMENT_VIEW';

    // Customers & Suppliers
    case CUSTOMER_VIEW   = 'CUSTOMER_VIEW';
    case CUSTOMER_CREATE = 'CUSTOMER_CREATE';
    case SUPPLIER_VIEW   = 'SUPPLIER_VIEW';
    case SUPPLIER_CREATE = 'SUPPLIER_CREATE';

    // Reports
    case REPORT_VIEW   = 'REPORT_VIEW';
    case REPORT_EXPORT = 'REPORT_EXPORT';

    // Branches & Settings
    case BRANCH_MANAGE   = 'BRANCH_MANAGE';
    case SETTINGS_MANAGE = 'SETTINGS_MANAGE';

    public function label(): string
    {
        return match ($this) {
            self::USER_VIEW   => 'View Users',
            self::USER_CREATE => 'Create Users',
            self::USER_UPDATE => 'Update Users',
            self::USER_DELETE => 'Delete Users',

            self::PRODUCT_VIEW   => 'View Products',
            self::PRODUCT_CREATE => 'Create Products',
            self::PRODUCT_UPDATE => 'Update Products',
            self::PRODUCT_DELETE => 'Delete Products',

            self::INVENTORY_VIEW   => 'View Inventory',
            self::INVENTORY_CREATE => 'Add Inventory',
            self::INVENTORY_ADJUST => 'Adjust Inventory',

            self::POS_CREATE => 'Create Sale (POS)',
            self::POS_VIEW   => 'View Sales',
            self::POS_CANCEL => 'Cancel Sale',

            self::PAYMENT_CREATE => 'Create Payment',
            self::PAYMENT_VIEW   => 'View Payments',

            self::CUSTOMER_VIEW   => 'View Customers',
            self::CUSTOMER_CREATE => 'Create Customers',
            self::SUPPLIER_VIEW   => 'View Suppliers',
            self::SUPPLIER_CREATE => 'Create Suppliers',

            self::REPORT_VIEW   => 'View Reports',
            self::REPORT_EXPORT => 'Export Reports',

            self::BRANCH_MANAGE   => 'Manage Branches',
            self::SETTINGS_MANAGE => 'Manage Settings',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::USER_VIEW, self::USER_CREATE,
            self::USER_UPDATE, self::USER_DELETE         => 'USERS',

            self::PRODUCT_VIEW, self::PRODUCT_CREATE,
            self::PRODUCT_UPDATE, self::PRODUCT_DELETE   => 'PRODUCTS',

            self::INVENTORY_VIEW, self::INVENTORY_CREATE,
            self::INVENTORY_ADJUST                       => 'INVENTORY',

            self::POS_CREATE, self::POS_VIEW,
            self::POS_CANCEL                             => 'POS',

            self::PAYMENT_CREATE, self::PAYMENT_VIEW     => 'PAYMENTS',

            self::CUSTOMER_VIEW, self::CUSTOMER_CREATE,
            self::SUPPLIER_VIEW, self::SUPPLIER_CREATE   => 'PEOPLE',

            self::REPORT_VIEW, self::REPORT_EXPORT       => 'REPORTS',

            self::BRANCH_MANAGE                          => 'BRANCHES',
            self::SETTINGS_MANAGE                        => 'SETTINGS',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'group' => $this->group(),
        ];
    }

    public static function toArrayForSelect(): array
    {
        return array_map(fn($case) => $case->toArray(), self::cases());
    }
}
