<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id',
        'author',
        'content',
        'author_avatar',
        'likes',
    ];

    protected $casts = [
        'likes' => 'integer',
    ];

    /**
     * Get the blog post that owns the comment
     */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }
}
