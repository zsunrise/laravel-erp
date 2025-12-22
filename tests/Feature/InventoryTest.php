<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        $this->product = Product::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    public function test_can_list_inventory()
    {
        Inventory::factory()->count(3)->create();

        $response = $this->getJson('/api/inventory');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'warehouse_id',
                        'quantity',
                        'available_quantity',
                    ],
                ],
            ]);
    }

    public function test_can_stock_in()
    {
        $response = $this->postJson('/api/inventory/stock-in', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'unit_cost' => 10.00,
            'reference_type' => 'test',
            'reference_no' => 'TEST001',
            'remark' => '测试入库',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'inventory',
                'transaction',
            ]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 100,
        ]);
    }

    public function test_can_stock_out()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this->postJson('/api/inventory/stock-out', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 30,
            'reference_type' => 'test',
            'reference_no' => 'TEST002',
            'remark' => '测试出库',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 70,
            'available_quantity' => 70,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $this->product->id,
            'type' => 'out',
            'quantity' => -30,
        ]);
    }

    public function test_cannot_stock_out_when_insufficient()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
            'available_quantity' => 50,
        ]);

        $response = $this->postJson('/api/inventory/stock-out', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);

        $response->assertStatus(400);
    }

    public function test_can_transfer_inventory()
    {
        $warehouse2 = Warehouse::factory()->create();
        
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this->postJson('/api/inventory/transfer', [
            'product_id' => $this->product->id,
            'from_warehouse_id' => $this->warehouse->id,
            'to_warehouse_id' => $warehouse2->id,
            'quantity' => 50,
            'reference_no' => 'TRANSFER001',
            'remark' => '测试调拨',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->product->id,
            'warehouse_id' => $warehouse2->id,
            'quantity' => 50,
        ]);
    }

    public function test_can_adjust_inventory()
    {
        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this->postJson('/api/inventory/adjust', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 120,
            'reference_no' => 'ADJUST001',
            'remark' => '盘点调整',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 120,
        ]);
    }

    public function test_can_list_inventory_transactions()
    {
        InventoryTransaction::factory()->count(5)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson('/api/inventory-transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product_id',
                        'type',
                        'quantity',
                    ],
                ],
            ]);
    }
}

