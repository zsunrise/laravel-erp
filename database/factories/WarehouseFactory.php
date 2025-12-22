<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition()
    {
        return [
            'name' => fake()->words(2, true) . '仓库',
            'code' => 'WH' . fake()->unique()->numerify('####'),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}

