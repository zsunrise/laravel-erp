<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SalesServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $salesService;
    protected $customer;
    protected $warehouse;
    protected $product;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = Customer::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
        $this->user = \App\Models\User::factory()->create();
        
        Auth::login($this->user);
        
        $inventoryService = new InventoryService();
        $this->salesService = new SalesService($inventoryService);
    }

    public function test_create_order_creates_order_with_items()
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

        $order = $this->salesService->createOrder($data);

        $this->assertInstanceOf(SalesOrder::class, $order);
        $this->assertEquals('draft', $order->status);
        $this->assertEquals(1, $order->items->count());
        $this->assertGreaterThan(0, $order->total_amount);
    }

    public function test_create_order_calculates_totals_correctly()
    {
        $data = [
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->format('Y-m-d'),
            'discount_amount' => 50.00,
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

        $order = $this->salesService->createOrder($data);

        // 小计 = 10 * 100 * (1 - 0.05) = 950
        // 税额 = 950 * 0.13 = 123.5
        // 总计 = 950 + 123.5 - 50 = 1023.5
        $this->assertEquals(950.00, $order->subtotal);
        $this->assertEquals(123.50, $order->tax_amount);
        $this->assertEquals(50.00, $order->discount_amount);
        $this->assertEquals(1023.50, $order->total_amount);
    }

    public function test_update_order_updates_order_and_items()
    {
        $order = SalesOrder::factory()->create([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $this->product->id,
        ]);

        $data = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 20,
                    'unit_price' => 150.00,
                    'tax_rate' => 13,
                ],
            ],
        ];

        $updatedOrder = $this->salesService->updateOrder($order->id, $data);

        $this->assertEquals(1, $updatedOrder->items->count());
        $this->assertEquals(20, $updatedOrder->items->first()->quantity);
        $this->assertEquals(150.00, $updatedOrder->items->first()->unit_price);
    }

    public function test_update_order_throws_exception_when_not_draft()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'approved',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('只能修改草稿状态的订单');

        $this->salesService->updateOrder($order->id, []);
    }

    public function test_approve_order_changes_status()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'draft',
        ]);

        $approvedOrder = $this->salesService->approveOrder($order->id);

        $this->assertEquals('approved', $approvedOrder->status);
        $this->assertEquals($this->user->id, $approvedOrder->approved_by);
        $this->assertNotNull($approvedOrder->approved_at);
    }

    public function test_approve_order_throws_exception_when_invalid_status()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'completed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('订单状态不允许审核');

        $this->salesService->approveOrder($order->id);
    }

    public function test_ship_goods_decreases_inventory()
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

        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 50.00,
        ]);

        $items = [
            [
                'item_id' => $item->id,
                'quantity' => 10,
            ],
        ];

        $result = $this->salesService->shipGoods($order->id, $items);

        $this->assertEquals(10, $item->fresh()->shipped_quantity);
        $this->assertEquals(90, $inventory->fresh()->quantity);
    }

    public function test_ship_goods_throws_exception_when_insufficient_inventory()
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

        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'available_quantity' => 5,
            'reserved_quantity' => 0,
            'average_cost' => 50.00,
        ]);

        $items = [
            [
                'item_id' => $item->id,
                'quantity' => 10,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('库存不足');

        $this->salesService->shipGoods($order->id, $items);
    }

    public function test_ship_goods_throws_exception_when_exceeds_order_quantity()
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

        $inventory = Inventory::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'available_quantity' => 100,
            'reserved_quantity' => 0,
            'average_cost' => 50.00,
        ]);

        $items = [
            [
                'item_id' => $item->id,
                'quantity' => 15,
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('发货数量超过订单数量');

        $this->salesService->shipGoods($order->id, $items);
    }

    public function test_cancel_order_changes_status_to_cancelled()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'draft',
        ]);

        $result = $this->salesService->cancelOrder($order->id);

        $this->assertEquals('cancelled', $result->status);
    }

    public function test_cancel_order_throws_exception_when_completed()
    {
        $order = SalesOrder::factory()->create([
            'status' => 'completed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('已完成的订单不能取消');

        $this->salesService->cancelOrder($order->id);
    }
}

