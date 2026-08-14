<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductDeleteWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    private function createProduct(string $sku, string $name, string $slug, Category $category, Brand $brand, bool $isActive = true): Product
    {
        $product = Product::create([
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => $isActive,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-' . $sku,
            'stock' => 10,
            'selling_price' => 50,
        ]);

        return $product;
    }

    public function test_delete_product_soft_deletes_it(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        $product = $this->createProduct('SKU-1', 'Product 1', 'product-1', $category, $brand);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNotNull($product->fresh()->deleted_at);
        $this->assertTrue(Product::withTrashed()->where('id', $product->id)->exists());
    }

    public function test_existing_order_remains_valid_after_soft_delete(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        $product = $this->createProduct('SKU-2', 'Product 2', 'product-2', $category, $brand);

        $order = Order::create([
            'order_number' => 'ORD-123',
            'customer_id' => 1,
            'status' => \App\Enums\OrderStatus::PENDING,
            'grand_total' => 50.00,
            'subtotal' => 50.00,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'country' => 'UK',
            'address' => '123 Main St',
            'city' => 'London',
            'state' => 'London',
            'pin_code' => 'SW1A 1AA',
            'shipping_method' => 'standard',
            'payment_method' => 'stripe',
            'payment_status' => \App\Enums\PaymentStatus::PENDING,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'price' => 50.00,
            'line_total' => 50.00,
        ]);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $orderItem->id,
            'product_id' => $product->id
        ]);
    }

    public function test_product_listing_excludes_soft_deleted_product(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        
        $product1 = $this->createProduct('SKU-3A', 'Product 3A', 'product-3a', $category, $brand);
        $product2 = $this->createProduct('SKU-3B', 'Product 3B', 'product-3b', $category, $brand);

        $product2->delete();

        $response = $this->getJson('/api/storefront/products');
        $response->assertOk();
        
        $data = $response->json('data');
        $ids = collect($data)->pluck('id')->all();
        
        $this->assertContains($product1->id, $ids);
        $this->assertNotContains($product2->id, $ids);
    }

    public function test_product_detail_returns_404_for_soft_deleted_and_inactive_product(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        
        $productActive = $this->createProduct('SKU-4A', 'Product 4A', 'product-4a', $category, $brand);
        $productInactive = $this->createProduct('SKU-4B', 'Product 4B', 'product-4b', $category, $brand, false);
        $productDeleted = $this->createProduct('SKU-4C', 'Product 4C', 'product-4c', $category, $brand);

        $productDeleted->delete();

        // Active product should be found
        $this->getJson('/api/storefront/products/product-4a')->assertOk();

        // Inactive product should return 404
        $this->getJson('/api/storefront/products/product-4b')->assertNotFound();

        // Soft-deleted product should return 404
        $this->getJson('/api/storefront/products/product-4c')->assertNotFound();
    }

    public function test_category_products_excludes_soft_deleted_product(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        
        $product1 = $this->createProduct('SKU-5A', 'Product 5A', 'product-5a', $category, $brand);
        $product2 = $this->createProduct('SKU-5B', 'Product 5B', 'product-5b', $category, $brand);

        $product2->delete();

        $response = $this->getJson('/api/storefront/products?category=category-a');
        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Product 5A', $data[0]['name']);
    }

    public function test_search_excludes_soft_deleted_product(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        
        $product1 = $this->createProduct('SKU-6A', 'SearchProduct', 'search-product-active', $category, $brand);
        $product2 = $this->createProduct('SKU-6B', 'SearchProduct', 'search-product-deleted', $category, $brand);

        $product2->delete();

        $response = $this->getJson('/api/storefront/products?search=SearchProduct');
        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_restore_makes_product_available_again(): void
    {
        $brand = Brand::create(['name' => 'Brand A', 'slug' => 'brand-a']);
        $category = Category::create(['name' => 'Category A', 'slug' => 'category-a', 'is_active' => true]);
        $product = $this->createProduct('SKU-9', 'Product 9', 'product-9', $category, $brand);

        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $product->restore();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null
        ]);

        $this->getJson('/api/storefront/products/product-9')->assertOk();
    }
}
