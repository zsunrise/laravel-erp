<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $managerRole = Role::where('slug', 'manager')->first();
        $operatorRole = Role::where('slug', 'operator')->first();

        // 创建管理员用户
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '系统管理员',
                'password' => Hash::make('admin123'),
                'phone' => '13800138000',
                'is_active' => true,
            ]
        );
        if (!$admin->roles->contains($adminRole->id)) {
            $admin->roles()->attach($adminRole->id);
        }

        // 创建经理用户
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => '业务经理',
                'password' => Hash::make('manager123'),
                'phone' => '13800138001',
                'is_active' => true,
            ]
        );
        if (!$manager->roles->contains($managerRole->id)) {
            $manager->roles()->attach($managerRole->id);
        }

        // 创建操作员用户
        $operator = User::firstOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => '操作员',
                'password' => Hash::make('operator123'),
                'phone' => '13800138002',
                'is_active' => true,
            ]
        );
        if (!$operator->roles->contains($operatorRole->id)) {
            $operator->roles()->attach($operatorRole->id);
        }
    }
}
