<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@eventloc.tg'],
            ['name' => 'Admin', 'password' => bcrypt('password')],
        );

        if (! $user->hasRole('admin')) {
            $user->assignRole($admin);
        }
    }
}
