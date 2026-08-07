<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'size',
        'buying_price',
        'margin',
        'tax_percentage',
        'expiry_date',
        'selling_price',
        'stock',
        'stock_in_order',
    ];

    protected $casts = [
        'buying_price' => 'decimal:2',
        'margin' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function getNameAttribute()
    {
        return $this->attributes['size'] ?? null;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['size'] = $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
