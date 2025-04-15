<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Store a newly created comment.
     */
    public function store(Request $request, $blogPostId)
    {
        // Check if blog post exists
        $blogPost = BlogPost::findOrFail($blogPostId);

        $validator = Validator::make($request->all(), [
            'author' => 'required|string|max:255',
            'content' => 'required|string',
            'author_avatar' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = new Comment($validator->validated());
        $comment->blog_post_id = $blogPostId;
        $comment->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'author' => $comment->author,
                'content' => $comment->content,
                'date' => $comment->created_at->toDateString(),
                'likes' => $comment->likes,
                'authorAvatar' => $comment->author_avatar,
            ],
            'message' => 'Comment added successfully',
        ], 201);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, $blogPostId, $id)
    {
        $comment = Comment::where('blog_post_id', $blogPostId)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $comment->id,
                'author' => $comment->author,
                'content' => $comment->content,
                'date' => $comment->created_at->toDateString(),
                'likes' => $comment->likes,
                'authorAvatar' => $comment->author_avatar,
            ],
            'message' => 'Comment updated successfully',
        ]);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy($blogPostId, $id)
    {
        $comment = Comment::where('blog_post_id', $blogPostId)
            ->where('id', $id)
            ->firstOrFail();

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Like a comment.
     */
    public function like($blogPostId, $id)
    {
        $comment = Comment::where('blog_post_id', $blogPostId)
            ->where('id', $id)
            ->firstOrFail();

        $comment->increment('likes');

        return response()->json([
            'success' => true,
            'data' => [
                'likes' => $comment->likes,
            ],
            'message' => 'Comment liked successfully',
        ]);
    }
}
