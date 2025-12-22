<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_category()
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(ProductCategory::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }

    public function test_product_belongs_to_unit()
    {
        $unit = Unit::factory()->create();
        $product = Product::factory()->create(['unit_id' => $unit->id]);

        $this->assertInstanceOf(Unit::class, $product->unit);
        $this->assertEquals($unit->id, $product->unit->id);
    }

    public function test_product_has_many_inventory()
    {
        $product = Product::factory()->create();
        $inventory1 = \App\Models\Inventory::factory()->create(['product_id' => $product->id]);
        $inventory2 = \App\Models\Inventory::factory()->create(['product_id' => $product->id]);

        $this->assertCount(2, $product->inventory);
        $this->assertTrue($product->inventory->contains($inventory1));
        $this->assertTrue($product->inventory->contains($inventory2));
    }

    public function test_product_price_casting()
    {
        $product = Product::factory()->create([
            'purchase_price' => 10.50,
            'sale_price' => 15.75,
            'cost_price' => 12.00,
        ]);

        // Laravel的decimal类型返回字符串
        $this->assertIsString($product->purchase_price);
        $this->assertIsString($product->sale_price);
        $this->assertIsString($product->cost_price);
        $this->assertEquals('10.50', $product->purchase_price);
        $this->assertEquals('15.75', $product->sale_price);
        $this->assertEquals('12.00', $product->cost_price);
    }

    public function test_product_is_active_casting()
    {
        $product = Product::factory()->create(['is_active' => true]);

        $this->assertIsBool($product->is_active);
        $this->assertTrue($product->is_active);
    }

    public function test_product_soft_deletes()
    {
        $product = Product::factory()->create();
        $productId = $product->id;

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $productId]);
        $this->assertNull(Product::find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }
}

