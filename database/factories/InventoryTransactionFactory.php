<?php

namespace Database\Factories;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => null,
            'type' => fake()->randomElement(['in', 'out', 'transfer', 'adjust']),
            'reference_type' => null,
            'reference_id' => null,
            'reference_no' => fake()->unique()->bothify('REF####'),
            'quantity' => fake()->numberBetween(-100, 100),
            'unit_cost' => fake()->randomFloat(2, 10, 100),
            'total_cost' => function (array $attributes) {
                return abs($attributes['quantity']) * $attributes['unit_cost'];
            },
            'user_id' => User::factory(),
            'remark' => fake()->sentence(),
            'transaction_date' => now(),
        ];
    }
}

