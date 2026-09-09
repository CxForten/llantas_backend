<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $permisos = [
        ['sales.create',      'Registrar ventas',        'ventas'],
        ['sales.void',        'Anular ventas',           'ventas'],
        ['sales.discount',    'Aplicar descuentos',      'ventas'],
        ['products.view',     'Ver productos',           'inventario'],
        ['products.manage',   'Crear y editar productos','inventario'],
        ['products.cost',     'Ver precios de costo',    'inventario'],
        ['stock.adjust',      'Ajustar inventario',      'inventario'],
        ['cash.open',         'Abrir caja',              'caja'],
        ['cash.close',        'Cerrar caja',             'caja'],
        ['reports.view',      'Ver reportes',            'reportes'],
        ['settings.manage',   'Configuración',           'config'],
        ['users.manage',      'Gestionar usuarios',      'config'],
    ];

    foreach ($permisos as [$slug, $name, $group]) {
        Permission::firstOrCreate(['slug' => $slug], compact('name', 'group'));
    }

    $business = Business::first();

    $admin = Role::firstOrCreate(
        ['business_id' => $business->id, 'slug' => 'admin'],
        ['name' => 'Administrador', 'is_system' => true]
    );
    $admin->permissions()->sync(Permission::pluck('id'));

    $vendedor = Role::firstOrCreate(
        ['business_id' => $business->id, 'slug' => 'vendedor'],
        ['name' => 'Vendedor', 'is_system' => true]
    );
    $vendedor->permissions()->sync(
        Permission::whereIn('slug', [
            'sales.create', 'products.view', 'cash.open', 'cash.close',
        ])->pluck('id')
    );

    User::whereNull('role_id')->update(['role_id' => $admin->id]);
}
}
