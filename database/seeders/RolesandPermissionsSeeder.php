<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesandPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // مسح الكاش
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // صلاحيات المنتجات
        Permission::create(['name' => "view products"]);
        Permission::create(['name' => "create products"]);
        Permission::create(['name' => "edit products"]);
        Permission::create(['name' => "delete products"]);

        // صلاحيات الطلبات
        Permission::create(['name' => "view orders"]);
        Permission::create(['name' => "create orders"]);
        Permission::create(['name' => "update orders"]);
        Permission::create(['name' => "cancel orders"]);

        // صلاحيات المستخدمين
        Permission::create(['name' => "view users"]);
        Permission::create(['name' => "edit users"]);

        // صلاحيات التوصيل
        Permission::create(['name' => "view deliveries"]);
        Permission::create(['name' => "update delivery status"]);

        // إنشاء الدور Admin
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view orders',
            'create orders',
            'update orders',
            'cancel orders',
            'view users',
            'edit users',
            'view deliveries',
            'update delivery status'
        ]);

        // إنشاء الدور Customer
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view products',
            'view orders',
            'create orders',
            'cancel orders'
        ]);

        // إنشاء الدور Delivery
        $deliveryRole = Role::create(['name' => 'delivery']);
        $deliveryRole->givePermissionTo([
            'view deliveries',
            'update delivery status',
            'view orders',
            'view products'
        ]);
    }
}
