<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Collection extends Model
{
    protected $table = 'collections';

    protected $fillable = [
        'name',
        'slug',
        'image',
    ];

    protected static function booted()
    {
        static::saving(function ($collection) {
            $slug = Str::slug($collection->name);
            $originalSlug = $slug;
            $count = 1;

            while (static::where('slug', $slug)->where('id', '!=', $collection->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $collection->slug = $slug;
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product');
    }
}
