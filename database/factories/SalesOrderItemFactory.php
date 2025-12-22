<?php

namespace Database\Factories;

use App\Models\SalesOrderItem;
use App\Models\SalesOrder;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition()
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'shipped_quantity' => 0,
            'unit_price' => fake()->randomFloat(2, 10, 100),
            'tax_rate' => fake()->randomFloat(2, 0, 20),
            'discount_rate' => fake()->randomFloat(2, 0, 10),
            'remark' => fake()->sentence(),
        ];
    }
}

