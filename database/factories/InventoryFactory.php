<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => null,
            'quantity' => fake()->numberBetween(0, 1000),
            'available_quantity' => function (array $attributes) {
                return $attributes['quantity'] ?? fake()->numberBetween(0, 1000);
            },
            'reserved_quantity' => 0,
            'average_cost' => fake()->randomFloat(2, 10, 100),
        ];
    }
}

