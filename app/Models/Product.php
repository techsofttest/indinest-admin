<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $with = ['fabric', 'color', 'occasion'];

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'brand_id',
        'supplier_code',
        'category_id',
        'key_features',
        'description',
        'size_chart',
        'style_fit_tips',
        'shipping_returns',
        'faq',
        'fabric_id',
        'color_id',
        'occasion_id',
        'featured_image',
        'is_featured',
        'is_active',
        'requires_direct_delivery',
        'allows_courier',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'requires_direct_delivery' => 'boolean',
        'allows_courier' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($product) {
            $slug = Str::slug($product->name);
            $originalSlug = $slug;
            $count = 1;
            while (static::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $product->slug = $slug;
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function department()
    {
        return $this->hasOneThrough(
            Department::class,
            Category::class,
            'id',
            'id',
            'category_id',
            'department_id'
        );
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'collection_product');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'review_product_id');
    }

    /**
     * Get the cheapest variant's selling price
     */
    public function getMinPriceAttribute()
    {
        return $this->variants->min('selling_price');
    }

    /**
     * Get the most expensive variant's selling price
     */
    public function getMaxPriceAttribute()
    {
        return $this->variants->max('selling_price');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function occasion()
    {
        return $this->belongsTo(Occasion::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
