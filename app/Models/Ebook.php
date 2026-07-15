<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ebook extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'author',
        'user_id',
        'price',
        'description',
        'image_cover',
        'url_files',
        'path_files',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user (author/publisher) who created/uploaded the ebook.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all users who purchased this ebook.
     */
    public function purchasers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_ebooks', 'ebook_id', 'user_id')
                    ->withTimestamps();
    }

    /**
     * Get all transactions associated with this ebook.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(EbookTransaction::class, 'ebook_id');
    }
}
