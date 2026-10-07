<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Products
            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            // Categories
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // Variants
            'variants.view',
            'variants.create',
            'variants.update',
            'variants.delete',

            // Attributes
            'attributes.view',
            'attributes.create',
            'attributes.update',
            'attributes.delete',

            // Attribute Values
            'attribute-values.view',
            'attribute-values.create',
            'attribute-values.update',
            'attribute-values.delete',

            // Product Images
            'product-images.view',
            'product-images.create',
            'product-images.update',
            'product-images.delete',

            // Orders
            'orders.view',
            'orders.update',
            'orders.cancel',
            'orders.status-update',

            // Payments
            'payments.view',
            'payments.update',

            // Customers
            'customers.view',
            'customers.update',

            // Inventory
            'inventory.view',
            'inventory.update',
            'inventory.adjust',

            // Reviews
            'reviews.view',
            'reviews.moderate',
            'reviews.reply',

            // Feedback
            'feedback.view',
            'feedback.reply',
            'feedback.moderate',

            // Coupons
            'coupons.view',
            'coupons.create',
            'coupons.update',
            'coupons.delete',

            // Reports
            'reports.view',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.suspend',

            // Roles
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.assign',

            // Settings
            'settings.view',
            'settings.update',

            // Audit Logs
            'audit-logs.view',
            'security.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
