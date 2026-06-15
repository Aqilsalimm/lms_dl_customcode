<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discussion extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'material_id',
        'parent_id',
        'body',
        'is_resolved'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
    ];

    /**
     * Get the user who posted the discussion/reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course associated with the discussion.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the material (lesson) associated with the discussion.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'material_id');
    }

    /**
     * Get the parent discussion.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Discussion::class, 'parent_id');
    }

    /**
     * Get the replies for this discussion.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Discussion::class, 'parent_id');
    }
}
