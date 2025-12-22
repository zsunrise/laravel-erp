<?php

namespace Tests\Unit;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryService;
    protected $product;
    protected $warehouse;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = new InventoryService();
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->user = \App\Models\User::factory()->create();
        
        Auth::login($this->user);
    }

    public function test_get_inventory_returns_existing_inventory()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->getInventory(
            $this->product->id,
            $this->warehouse->id
        );

        $this->assertInstanceOf(Inventory::class, $result);
        $this->assertEquals($inventory->id, $result->id);
    }

    public function test_get_inventory_returns_null_when_not_exists()
    {
        $result = $this->inventoryService->getInventory(
            $this->product->id,
            $this->warehouse->id
        );

        $this->assertNull($result);
    }

    public function test_get_or_create_inventory_returns_existing()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->getOrCreateInventory(
            $this->product->id,
            $this->warehouse->id
        );

        $this->assertEquals($inventory->id, $result->id);
        $this->assertEquals(1, Inventory::count());
    }

    public function test_get_or_create_inventory_creates_new()
    {
        $result = $this->inventoryService->getOrCreateInventory(
            $this->product->id,
            $this->warehouse->id
        );

        $this->assertInstanceOf(Inventory::class, $result);
        $this->assertEquals(0, $result->quantity);
        $this->assertEquals(0, $result->available_quantity);
    }

    public function test_stock_in_creates_inventory_and_transaction()
    {
        $result = $this->inventoryService->stockIn(
            $this->product->id,
            $this->warehouse->id,
            100,
            10.00,
            [
                'reference_type' => 'test',
                'reference_id' => 1,
                'reference_no' => 'TEST001',
                'remark' => '测试入库',
            ]
        );

        $this->assertArrayHasKey('inventory', $result);
        $this->assertArrayHasKey('transaction', $result);
        $this->assertEquals(100, $result['inventory']->quantity);
        $this->assertEquals(10.00, $result['inventory']->average_cost);
        $this->assertEquals('in', $result['transaction']->type);
        $this->assertEquals(100, $result['transaction']->quantity);
    }

    public function test_stock_in_updates_existing_inventory()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->stockIn(
            $this->product->id,
            $this->warehouse->id,
            50,
            12.00
        );

        $this->assertEquals(150, $result['inventory']->quantity);
        // 平均成本 = (100 * 10 + 50 * 12) / 150 = 10.67
        $this->assertEquals(10.67, round($result['inventory']->average_cost, 2));
    }

    public function test_stock_out_decreases_inventory()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->stockOut(
            $this->product->id,
            $this->warehouse->id,
            30,
            10.00,
            [
                'reference_type' => 'test',
                'reference_id' => 1,
                'reference_no' => 'TEST001',
            ]
        );

        $this->assertEquals(70, $result['inventory']->quantity);
        $this->assertEquals(70, $result['inventory']->available_quantity);
        $this->assertEquals('out', $result['transaction']->type);
        $this->assertEquals(-30, $result['transaction']->quantity);
    }

    public function test_stock_out_throws_exception_when_insufficient()
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
        $this->expectExceptionMessage('库存不足');

        $this->inventoryService->stockOut(
            $this->product->id,
            $this->warehouse->id,
            60
        );
    }

    public function test_stock_out_uses_average_cost_when_unit_cost_zero()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 15.50,
        ]);

        $result = $this->inventoryService->stockOut(
            $this->product->id,
            $this->warehouse->id,
            30,
            0
        );

        $this->assertEquals(15.50, $result['transaction']->unit_cost);
    }

    public function test_transfer_moves_inventory_between_warehouses()
    {
        $warehouse2 = Warehouse::factory()->create();
        
        $fromInventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->transfer(
            $this->product->id,
            $this->warehouse->id,
            $warehouse2->id,
            50,
            [
                'reference_no' => 'TRANSFER001',
                'remark' => '测试调拨',
            ]
        );

        $this->assertEquals(50, $result['from_inventory']->quantity);
        $this->assertEquals(50, $result['to_inventory']->quantity);
        $this->assertEquals(10.00, $result['to_inventory']->average_cost);
        
        // 检查交易记录
        $transactions = InventoryTransaction::where('reference_no', 'TRANSFER001')->get();
        $this->assertEquals(2, $transactions->count());
    }

    public function test_transfer_throws_exception_when_insufficient()
    {
        $warehouse2 = Warehouse::factory()->create();
        
        $fromInventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 50,
            'reserved_quantity' => 50,
            'average_cost' => 10.00,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('库存不足');

        $this->inventoryService->transfer(
            $this->product->id,
            $this->warehouse->id,
            $warehouse2->id,
            60
        );
    }

    public function test_adjust_updates_inventory_to_target_quantity()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->adjust(
            $this->product->id,
            $this->warehouse->id,
            120,
            10.00,
            [
                'reference_no' => 'ADJUST001',
                'remark' => '盘点调整',
            ]
        );

        $this->assertEquals(120, $result['inventory']->quantity);
        $this->assertEquals(20, $result['transaction']->quantity);
    }

    public function test_adjust_decreases_when_target_less_than_current()
    {
        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 10.00,
        ]);

        $result = $this->inventoryService->adjust(
            $this->product->id,
            $this->warehouse->id,
            80
        );

        $this->assertEquals(80, $result['inventory']->quantity);
        $this->assertEquals(-20, $result['transaction']->quantity);
    }
}

