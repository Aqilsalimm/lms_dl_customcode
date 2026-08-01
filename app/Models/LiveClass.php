<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveClass extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'delivery_mode',
        'mode',
        'meeting_link',
        'location_venue',
        'venue_name',
        'venue_address',
        'gmaps_url',
        'gmaps_embed_url',
        'offline_capacity',
        'recording_url',
        'documentation_urls',
        'start_time',
        'end_time',
        'is_published',
    ];

    protected $casts = [
        'documentation_urls' => 'array', // Otomatis cast JSON DB ke Array PHP/JS
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'offline_capacity' => 'integer',
        'is_published' => 'boolean',
    ];

    // Helper Accessor untuk pengecekan cepat di Vue/Inertia
    protected $appends = ['is_offline', 'has_recording', 'has_documentation'];

    public function isOffline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->delivery_mode === 'offline' || $this->mode === 'offline'
        );
    }

    public function hasRecording(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->recording_url)
        );
    }

    public function hasDocumentation(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->documentation_urls) && is_array($this->documentation_urls) && count($this->documentation_urls) > 0
        );
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classEnrollments(): HasMany
    {
        return $this->hasMany(ClassEnrollment::class);
    }
}
