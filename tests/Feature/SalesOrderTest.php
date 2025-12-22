<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        $this->customer = Customer::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_can_list_sales_orders()
    {
        SalesOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/sales-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_no',
                        'customer_id',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_can_create_sales_order()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'unit_price' => 100.00,
                    'tax_rate' => 13,
                    'discount_rate' => 5,
                ],
            ],
        ];

        $response = $this->postJson('/api/sales-orders', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'order_no',
                'customer_id',
                'status',
                'items',
            ]);

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('sales_order_items', [
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
    }

    public function test_can_show_sales_order()
    {
        $order = SalesOrder::factory()->create();
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id]);

        $response = $this->getJson("/api/sales-orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'order_no' => $order->order_no,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'items',
                ],
            ]);
    }

    public function test_can_approve_sales_order()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/sales-orders/{$order->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    public function test_can_ship_goods()
    {
        $order = SalesOrder::factory()->create([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
        ]);

        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'shipped_quantity' => 0,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
        ]);

        $response = $this->postJson("/api/sales-orders/{$order->id}/ship", [
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales_order_items', [
            'id' => $item->id,
            'shipped_quantity' => 10,
        ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'quantity' => 90,
        ]);
    }

    public function test_cannot_ship_when_insufficient_inventory()
    {
        $order = SalesOrder::factory()->create([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
        ]);

        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $inventory = Inventory::factory()->create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'available_quantity' => 5,
        ]);

        $response = $this->postJson("/api/sales-orders/{$order->id}/ship", [
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertStatus(400);
    }

    public function test_can_cancel_sales_order()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/sales-orders/{$order->id}/cancel");

        $response->assertStatus(200);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_completed_order()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/sales-orders/{$order->id}/cancel");

        $response->assertStatus(400);
    }
}

