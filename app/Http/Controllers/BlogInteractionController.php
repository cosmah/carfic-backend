<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogInteractionController extends Controller
{
    /**
     * Like a blog post.
     */
    public function like($id)
    {
        $blogPost = BlogPost::findOrFail($id);
        $blogPost->increment('likes');

        return response()->json([
            'success' => true,
            'data' => [
                'likes' => $blogPost->likes,
            ],
            'message' => 'Blog post liked successfully',
        ]);
    }

    /**
     * Dislike a blog post.
     */
    public function dislike($id)
    {
        $blogPost = BlogPost::findOrFail($id);
        $blogPost->increment('dislikes');

        return response()->json([
            'success' => true,
            'data' => [
                'dislikes' => $blogPost->dislikes,
            ],
            'message' => 'Blog post disliked successfully',
        ]);
    }

    /**
     * View a blog post (increment view count).
     */
    public function view($id)
    {
        $blogPost = BlogPost::findOrFail($id);
        $blogPost->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => [
                'views' => $blogPost->views,
            ],
            'message' => 'Blog post view recorded successfully',
        ]);
    }
}
