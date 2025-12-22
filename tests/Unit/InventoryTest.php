<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    public function test_inventory_increase_updates_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $inventory->increase(50, 12.00);

        $this->assertEquals(150, $inventory->quantity);
        $this->assertEquals(150, $inventory->available_quantity);
    }

    public function test_inventory_increase_calculates_average_cost()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $inventory->increase(50, 12.00);

        // 平均成本 = (100 * 10 + 50 * 12) / 150 = 10.67
        $this->assertEquals(10.67, round($inventory->average_cost, 2));
    }

    public function test_inventory_increase_sets_cost_when_zero_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
            'available_quantity' => 0,
            'reserved_quantity' => 0,
            'average_cost' => 0,
        ]);

        $inventory->increase(100, 15.00);

        $this->assertEquals(15.00, $inventory->average_cost);
        $this->assertEquals(100, $inventory->quantity);
    }

    public function test_inventory_decrease_updates_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $inventory->decrease(30);

        $this->assertEquals(70, $inventory->quantity);
        $this->assertEquals(70, $inventory->available_quantity);
    }

    public function test_inventory_decrease_throws_exception_when_insufficient()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('可用库存不足');

        $inventory->decrease(150);
    }

    public function test_inventory_reserve_updates_reserved_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $inventory->reserve(30);

        $this->assertEquals(70, $inventory->available_quantity);
        $this->assertEquals(30, $inventory->reserved_quantity);
        $this->assertEquals(100, $inventory->quantity);
    }

    public function test_inventory_reserve_throws_exception_when_insufficient()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 50,
            'reserved_quantity' => 50,
            'average_cost' => 10.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('可用库存不足');

        $inventory->reserve(60);
    }

    public function test_inventory_release_updates_available_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 70,
            'reserved_quantity' => 30,
            'average_cost' => 10.00,
        ]);

        $inventory->release(20);

        $this->assertEquals(90, $inventory->available_quantity);
        $this->assertEquals(10, $inventory->reserved_quantity);
        $this->assertEquals(100, $inventory->quantity);
    }

    public function test_inventory_release_throws_exception_when_insufficient()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 70,
            'reserved_quantity' => 30,
            'average_cost' => 10.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('预留库存不足');

        $inventory->release(40);
    }
}

