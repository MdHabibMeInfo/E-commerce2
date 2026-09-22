<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrator' => [
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
                'users.suspend',

                // Roles
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.assign',

                // Settings
                'settings.view',
                'settings.update',

                // Audit Logs
                'audit-logs.view',
            ],

            'Product Manager' => [
                'products.view',
                'products.create',
                'products.update',
                'products.delete',

                'categories.view',
                'categories.create',
                'categories.update',
                'categories.delete',

                'variants.view',
                'variants.create',
                'variants.update',
                'variants.delete',

                'attributes.view',
                'attributes.create',
                'attributes.update',
                'attributes.delete',

                'attribute-values.view',
                'attribute-values.create',
                'attribute-values.update',
                'attribute-values.delete',

                'product-images.view',
                'product-images.create',
                'product-images.update',
                'product-images.delete',

                'inventory.view',
            ],

            'Order Manager' => [
                'orders.view',
                'orders.update',
                'orders.cancel',
                'orders.status-update',

                'payments.view',
                'payments.update',

                'customers.view',
            ],

            'Inventory Manager' => [
                'inventory.view',
                'inventory.update',
                'inventory.adjust',

                'products.view',
                'variants.view',
                'categories.view',

                'orders.view',
            ],

            'Customer Support' => [
                'customers.view',
                'customers.update',

                'orders.view',
                'orders.update',
                'orders.status-update',

                'reviews.view',
                'reviews.moderate',
                'reviews.reply',

                'feedback.view',
                'feedback.reply',
                'feedback.moderate',
            ],
        ];

        foreach ($roles as $roleName => $permissionNames) {
            $role = Role::findOrCreate(
                $roleName,
                'web'
            );

            $role->update([
                'is_system' => true,
                'is_active' => true,
            ]);

            $permissions = Permission::whereIn(
                'name',
                $permissionNames
            )->get();

            $role->syncPermissions($permissions);
        }
    }
}
