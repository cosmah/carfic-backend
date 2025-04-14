<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'excerpt',
        'content',
        'image',
        'author',
        'author_avatar',
        'likes',
        'dislikes',
        'views',
        'read_time',
        'category',
    ];

    protected $casts = [
        'likes' => 'integer',
        'dislikes' => 'integer',
        'views' => 'integer',
        'read_time' => 'integer',
    ];

    /**
     * Get all comments for the blog post
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all tags for the blog post
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('views');
    }
}
