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
        $admin = User::create([
            'name' => '系统管理员',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'phone' => '13800138000',
            'is_active' => true,
        ]);
        $admin->roles()->attach($adminRole->id);

        // 创建经理用户
        $manager = User::create([
            'name' => '业务经理',
            'email' => 'manager@example.com',
            'password' => Hash::make('manager123'),
            'phone' => '13800138001',
            'is_active' => true,
        ]);
        $manager->roles()->attach($managerRole->id);

        // 创建操作员用户
        $operator = User::create([
            'name' => '操作员',
            'email' => 'operator@example.com',
            'password' => Hash::make('operator123'),
            'phone' => '13800138002',
            'is_active' => true,
        ]);
        $operator->roles()->attach($operatorRole->id);
    }
}
