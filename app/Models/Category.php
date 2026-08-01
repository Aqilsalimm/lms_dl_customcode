<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'parent_id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            } else {
                $category->slug = Str::slug($category->slug);
            }
        });

        static::saved(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('shared_categories_list');
            \Illuminate\Support\Facades\Cache::forget('catalog_categories');
        });

        static::deleted(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('shared_categories_list');
            \Illuminate\Support\Facades\Cache::forget('catalog_categories');
        });
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
