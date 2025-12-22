<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_products()
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'purchase_price',
                        'sale_price',
                    ],
                ],
            ]);
    }

    public function test_can_create_product()
    {
        $category = ProductCategory::factory()->create();
        $unit = Unit::factory()->create();

        $data = [
            'category_id' => $category->id,
            'name' => '测试商品',
            'sku' => 'TEST001',
            'barcode' => '1234567890123',
            'unit_id' => $unit->id,
            'purchase_price' => 10.00,
            'sale_price' => 15.00,
            'cost_price' => 12.00,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/products', $data);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'sku',
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST001',
            'name' => '测试商品',
        ]);
    }

    public function test_can_show_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
            ]);
    }

    public function test_can_update_product()
    {
        $product = Product::factory()->create([
            'name' => '原始名称',
        ]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'name' => '更新后的名称',
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'sku' => $product->sku,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => '更新后的名称',
        ]);
    }

    public function test_can_delete_product()
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_can_search_products()
    {
        Product::factory()->create(['name' => '测试商品A']);
        Product::factory()->create(['name' => '测试商品B']);
        Product::factory()->create(['name' => '其他商品']);

        $response = $this->getJson('/api/products?search=测试');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}

