<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roleAdmin = Role::create(['name' => 'administrator']);
        $permission = Permission::create(['name' => 'administrator']);
        $roleAdmin->givePermissionTo($permission);
        $admin = User::where('email','suarez@gmail.com') -> first();
        $admin->assignRole('administrator');
    }
}
