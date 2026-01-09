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
        // مسح كاش الصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */
        $permissions = [

            // Products
            'view products',
            'create products',
            'update products',
            'delete products',
            'restore products',
            'force delete products',

            // Orders
            'view orders',
            'create orders',
            'update orders',
            'cancel orders',

            // Users
            'view users',
            'update users',

            // Deliveries
            'view deliveries',
            'update deliveries',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        // Admin → كل الصلاحيات
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // Customer
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->syncPermissions([
            'view products',
            'view orders',
            'create orders',
            'cancel orders',
        ]);

        // Delivery
        $delivery = Role::firstOrCreate(['name' => 'delivery']);
        $delivery->syncPermissions([
            'view products',
            'view orders',
            'view deliveries',
            'update deliveries',
        ]);
    }
}
