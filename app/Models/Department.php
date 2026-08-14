<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Department extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image',
        'description',
        'sort_order',
    ];

    protected static function booted()
    {
        static::saving(function ($department) {
            $slug = Str::slug($department->name);
            $originalSlug = $slug;
            $count = 1;

            while (static::where('slug', $slug)->where('id', '!=', $department->id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            $department->slug = $slug;
        });

        static::deleting(function ($department) {
            if ($department->categories()->exists()) {
                throw ValidationException::withMessages([
                    'department' => 'This department cannot be deleted because it is assigned to one or more categories.',
                ]);
            }
        });
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id')->orderBy('sort_order');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
}