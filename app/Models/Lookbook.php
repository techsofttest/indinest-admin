<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Lookbook extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'model_image',
        'model_alt',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($lookbook) {
            if (empty($lookbook->slug)) {
                $slug = Str::slug($lookbook->title);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $lookbook->id)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $lookbook->slug = $slug;
            }
        });
    }

    public function lookbookProducts()
    {
        return $this->hasMany(LookbookProduct::class)->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lookbook_products')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
