<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Men', 'slug' => 'men'],
            ['name' => 'Women', 'slug' => 'women'],
            ['name' => 'Kids', 'slug' => 'kids'],
            ['name' => 'Gifts', 'slug' => 'gifts'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['slug' => $department['slug']],
                ['name' => $department['name']]
            );
        }
    }
}
