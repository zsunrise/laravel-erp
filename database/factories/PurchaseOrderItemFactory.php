<?php

namespace Database\Factories;

use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrder;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition()
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 100),
            'received_quantity' => 0,
            'unit_price' => fake()->randomFloat(2, 10, 100),
            'tax_rate' => fake()->randomFloat(2, 0, 20),
            'discount_rate' => fake()->randomFloat(2, 0, 10),
            'remark' => fake()->sentence(),
        ];
    }
}

