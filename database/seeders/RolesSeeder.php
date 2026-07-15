<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la caché de roles y permisos antes de sembrar.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'manager',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'operator',
            'guard_name' => 'web',
        ]);

        // El primer usuario existente será el administrador inicial.
        $admin = User::find(1);

        if ($admin) {
            $admin->syncRoles(['admin']);
        }
    }
}