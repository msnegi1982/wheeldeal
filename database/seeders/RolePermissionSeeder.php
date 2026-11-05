<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resources = [
            'vehicles', 'brands', 'models', 'categories', 'fuel_types', 'cities',
            'features', 'variants', 'requests', 'enquiries', 'contact_enquiries',
            'seo_pages', 'redirects', 'imports', 'users',
        ];

        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'export'];

        $permissions = [];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions[] = Permission::findOrCreate("{$action}_{$resource}", 'web');
            }
        }

        $superAdmin = Role::findOrCreate(UserRole::SUPER_ADMIN->label(), 'web');
        $superAdmin->syncPermissions($permissions);

        $editor = Role::findOrCreate(UserRole::EDITOR->label(), 'web');
        $editor->syncPermissions(
            collect($permissions)
                ->filter(function (Permission $permission) {
                    $name = $permission->name;

                    if (str_ends_with($name, '_users')) {
                        return false;
                    }

                    if (in_array($name, ['delete_imports', 'delete_redirects'], true)) {
                        return false;
                    }

                    return true;
                })
                ->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
