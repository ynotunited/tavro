<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles & permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Define all granular permissions ──────────────────────────────────
        $permissions = [
            // Orders
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.void',
            'orders.discount',

            // Payments
            'payments.create',
            'payments.refund',
            'payments.view',

            // Menu / Catalog
            'menu.view',
            'menu.manage',

            // Inventory
            'inventory.view',
            'inventory.manage',
            'inventory.adjustments',

            // Stock
            'stock.purchase',
            'stock.transfer',

            // Reports
            'reports.sales',
            'reports.financial',
            'reports.inventory',
            'reports.staff',

            // Staff / Users
            'staff.view',
            'staff.manage',

            // Settings
            'settings.view',
            'settings.manage',

            // Branches
            'branches.view',
            'branches.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ── Define roles with their permission sets ───────────────────────────
        $roles = [
            'owner' => $permissions, // All permissions

            'general_manager' => [
                'orders.view', 'orders.create', 'orders.edit', 'orders.void', 'orders.discount',
                'payments.create', 'payments.refund', 'payments.view',
                'menu.view', 'menu.manage',
                'inventory.view', 'inventory.manage', 'inventory.adjustments',
                'stock.purchase', 'stock.transfer',
                'reports.sales', 'reports.financial', 'reports.inventory', 'reports.staff',
                'staff.view', 'staff.manage',
                'settings.view',
                'branches.view',
            ],

            'branch_manager' => [
                'orders.view', 'orders.create', 'orders.edit', 'orders.void', 'orders.discount',
                'payments.create', 'payments.refund', 'payments.view',
                'menu.view',
                'inventory.view', 'inventory.manage', 'inventory.adjustments',
                'stock.transfer',
                'reports.sales', 'reports.inventory', 'reports.staff',
                'staff.view', 'staff.manage',
                'settings.view',
                'branches.view',
            ],

            'cashier' => [
                'orders.view', 'orders.create', 'orders.edit',
                'payments.create', 'payments.view',
                'menu.view',
                'inventory.view',
                'reports.sales',
            ],

            'waiter' => [
                'orders.view', 'orders.create', 'orders.edit',
                'menu.view',
            ],

            'bartender' => [
                'orders.view', 'orders.create', 'orders.edit',
                'menu.view',
                'inventory.view',
            ],

            'kitchen_staff' => [
                'orders.view',
                'menu.view',
            ],

            'inventory_manager' => [
                'menu.view', 'menu.manage',
                'inventory.view', 'inventory.manage', 'inventory.adjustments',
                'stock.purchase', 'stock.transfer',
                'reports.inventory',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($rolePermissions);
        }

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
