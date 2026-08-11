<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LookbookProduct extends Model
{
    protected $table = 'lookbook_products';

    protected $fillable = [
        'lookbook_id',
        'product_id',
        'sort_order',
    ];

    public function lookbook()
    {
        return $this->belongsTo(Lookbook::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
