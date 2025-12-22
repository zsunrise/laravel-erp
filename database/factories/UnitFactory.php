<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition()
    {
        return [
            'name' => fake()->randomElement(['个', '件', '箱', '包', '公斤', '吨', '米', '升']),
            'code' => fake()->unique()->lexify('???'),
            'sort' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}

