<?php

namespace Database\Factories;

use App\Models\SalesOrder;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition()
    {
        return [
            'order_no' => 'SO' . date('YmdHis') . rand(1000, 9999),
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_date' => fake()->date(),
            'delivery_date' => fake()->date(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'created_by' => User::factory(),
        ];
    }
}

