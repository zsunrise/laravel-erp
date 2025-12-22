<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        return [
            'order_no' => 'PO' . date('YmdHis') . rand(1000, 9999),
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_date' => fake()->date(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'created_by' => User::factory(),
        ];
    }
}

