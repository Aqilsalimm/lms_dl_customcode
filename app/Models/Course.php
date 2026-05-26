<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'description',
        'about',
        'thumbnail',
        'bg_color',
        'icon_type',
        'price',
        'level',
        'capacity',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'duration',
        'sessions'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->slug)) {
                $course->slug = Str::slug($course->title) . '-' . Str::random(5);
            }
        });
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'buyable');
    }

    // Helper to get total duration
    public function getDurationAttribute()
    {
        return $this->lessons()->sum('duration_minutes') . ' Menit';
    }

    // Helper to get total sessions (lessons)
    public function getSessionsAttribute()
    {
        return $this->lessons()->count() . ' Sesi';
    }
}
