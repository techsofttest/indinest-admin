<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_belong_to_multiple_collections(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'SKU-001',
            'category_id' => null,
        ]);

        $collectionOne = Collection::create([
            'name' => 'New Arrivals',
            'slug' => 'new-arrivals',
        ]);

        $collectionTwo = Collection::create([
            'name' => 'Best Sellers',
            'slug' => 'best-sellers',
        ]);

        $product->collections()->attach([$collectionOne->id, $collectionTwo->id]);

        $this->assertTrue($product->collections()->whereKey($collectionOne->id)->exists());
        $this->assertTrue($product->collections()->whereKey($collectionTwo->id)->exists());
        $this->assertCount(2, $product->fresh()->collections);
    }
}
