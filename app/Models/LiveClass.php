<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveClass extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'delivery_mode',
        'meeting_link',
        'location_venue',
        'recording_url',
        'documentation_urls',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'documentation_urls' => 'array', // Otomatis cast JSON DB ke Array PHP/JS
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // Helper Accessor untuk pengecekan cepat di Vue/Inertia
    protected $appends = ['is_offline', 'has_recording', 'has_documentation'];

    public function isOffline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->delivery_mode === 'offline'
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
}
