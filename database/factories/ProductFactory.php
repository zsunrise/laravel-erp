<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => fake()->words(3, true),
            'sku' => 'SKU' . fake()->unique()->numerify('#######'),
            'barcode' => fake()->ean13(),
            'description' => fake()->sentence(),
            'unit_id' => Unit::factory(),
            'purchase_price' => fake()->randomFloat(2, 10, 100),
            'sale_price' => fake()->randomFloat(2, 50, 200),
            'cost_price' => fake()->randomFloat(2, 20, 80),
            'min_stock' => fake()->numberBetween(10, 50),
            'max_stock' => fake()->numberBetween(500, 1000),
            'is_active' => true,
        ];
    }
}

