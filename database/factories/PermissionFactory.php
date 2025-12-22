<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition()
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'group' => fake()->randomElement(['system', 'basic', 'inventory', 'purchase', 'sales', 'production', 'finance']),
            'description' => fake()->sentence(),
        ];
    }
}

