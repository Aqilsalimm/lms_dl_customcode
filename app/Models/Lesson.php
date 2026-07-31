<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'content',
        'video_url',
        'slide_url',
        'slide_content',
        'duration_minutes',
        'sort_order'
    ];

    protected static function booted()
    {
        static::saved(function ($lesson) {
            $lesson->invalidateCourseCache();
        });

        static::deleted(function ($lesson) {
            $lesson->invalidateCourseCache();
        });
    }

    private function invalidateCourseCache()
    {
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function discussions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Discussion::class, 'material_id');
    }
}
