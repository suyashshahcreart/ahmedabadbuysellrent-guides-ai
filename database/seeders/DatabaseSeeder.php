<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (array_unique(PermissionCatalog::all()) as $p) {
            Permission::findOrCreate($p);
        }

        $super = Role::findOrCreate('superAdmin');
        $super->syncPermissions(Permission::all());
        Role::findOrCreate('admin')->syncPermissions(Permission::all());
        Role::findOrCreate('editor')->syncPermissions(PermissionCatalog::editor());
        Role::findOrCreate('author')->syncPermissions(PermissionCatalog::author());
        Role::findOrCreate('seoManager')->syncPermissions(PermissionCatalog::seoManager());

        $user = User::query()->firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@guides.ahmedabadbuysellrent.in')],
            [
                'name' => 'Guides Admin',
                'password' => env('ADMIN_PASSWORD', 'GuidesAdmin2026!'),
            ]
        );
        if (! $user->hasRole('superAdmin')) {
            $user->assignRole('superAdmin');
        }
    }
}
