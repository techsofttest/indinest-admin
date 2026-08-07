<?php

namespace Database\Seeders;

use App\Models\Collection;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $collections = [
            'New Arrivals',
            'Best Sellers',
            'Featured',
            'Sale',
            'Gift Ideas',
        ];

        foreach ($collections as $name) {
            Collection::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
