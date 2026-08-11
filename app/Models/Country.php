<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'checkout_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
