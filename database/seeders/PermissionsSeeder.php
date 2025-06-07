<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionsSeeder extends Seeder
{
    private array $modules = [
        'permission', 'project', 'project status', 'role', 'ticket',
        'ticket priority', 'ticket status', 'ticket type', 'user',
        'activity', 'sprint', 'comment', 'customer feedback' // Added customer feedback
    ];

    private array $pluralActions = [
        'List'
    ];

    private array $singularActions = [
        'View', 'Create', 'Update', 'Delete'
    ];

    private array $extraPermissions = [
        'Manage general settings', 'Import from Jira',
        'List timesheet data', 'View timesheet dashboard'
    ];

    private string $defaultRole = 'Default role';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create profiles
        foreach ($this->modules as $module) {
            $plural = Str::plural($module);
            $singular = $module;
            foreach ($this->pluralActions as $action) {
                Permission::firstOrCreate([
                    'name' => $action . ' ' . $plural
                ]);
            }
            foreach ($this->singularActions as $action) {
                Permission::firstOrCreate([
                    'name' => $action . ' ' . $singular
                ]);
            }
        }

        foreach ($this->extraPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission
            ]);
        }

        // Create default role
        $role = Role::firstOrCreate([
            'name' => $this->defaultRole
        ]);
        $settings = app(GeneralSettings::class);
        $settings->default_role = $role->id;
        $settings->save();

        // Add all permissions to default role
        $role->syncPermissions(Permission::all()->pluck('name')->toArray());

        // Assign default role to first database user
        if ($user = User::first()) {
            $user->syncRoles([$this->defaultRole]);
        }

        // Setup specific roles with limited permissions
        $this->createClientRole();
        $this->createAdminRole();
        $this->createSuperAdminRole();
    }

    private function createClientRole()
    {
        $clientRole = Role::firstOrCreate(['name' => 'Client']);

        $clientPermissions = [
            'List customer feedbacks',
            'View customer feedback',
            'Create customer feedback',
            'Update customer feedback',
            'View project status',
            'View sprint'
        ];

        $existingPermissions = Permission::whereIn('name', $clientPermissions)->pluck('name')->toArray();
        $clientRole->syncPermissions($existingPermissions);
    }

    private function createAdminRole()
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Admin bisa semua kecuali manage roles & permissions
        $excludedPermissions = [
            'List roles', 'View role', 'Create role', 'Update role', 'Delete role',
            'List permissions', 'View permission', 'Create permission', 'Update permission', 'Delete permission'
        ];

        $adminPermissions = Permission::whereNotIn('name', $excludedPermissions)->pluck('name')->toArray();
        $adminRole->syncPermissions($adminPermissions);
    }

    private function createSuperAdminRole()
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Super Admin bisa semua
        $superAdminRole->syncPermissions(Permission::all()->pluck('name')->toArray());
    }
}
