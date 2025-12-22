<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
        
        $this->supplier = Supplier::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_can_list_purchase_orders()
    {
        PurchaseOrder::factory()->count(3)->create();

        $response = $this->getJson('/api/purchase-orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_no',
                        'supplier_id',
                        'status',
                    ],
                ],
            ]);
    }

    public function test_can_create_purchase_order()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 50,
                    'unit_price' => 80.00,
                    'tax_rate' => 13,
                ],
            ],
        ];

        $response = $this->postJson('/api/purchase-orders', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'order_no',
                'supplier_id',
                'status',
                'items',
            ]);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);
    }

    public function test_can_approve_purchase_order()
    {
        $order = PurchaseOrder::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/purchase-orders/{$order->id}/approve");

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ]);
    }

    public function test_can_receive_goods()
    {
        $order = PurchaseOrder::factory()->create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'approved',
        ]);

        $item = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'received_quantity' => 0,
        ]);

        $response = $this->postJson("/api/purchase-orders/{$order->id}/receive", [
            'items' => [
                [
                    'item_id' => $item->id,
                    'quantity' => 50,
                    'unit_cost' => 80.00,
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchase_order_items', [
            'id' => $item->id,
            'received_quantity' => 50,
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
        ]);
    }

    public function test_can_cancel_purchase_order()
    {
        $order = PurchaseOrder::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->postJson("/api/purchase-orders/{$order->id}/cancel");

        $response->assertStatus(200);

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
    }
}

