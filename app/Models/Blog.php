<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'image',
        'category',
        'status',
    ];

    /**
     * Get the author admin of this blog post
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
