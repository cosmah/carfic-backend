<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogPostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $query = BlogPost::with(['comments', 'tags']);

        // Filter by search term
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('excerpt', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        // Filter by category
        if ($request->has('category') && $request->input('category') !== 'All') {
            $query->where('category', $request->input('category'));
        }

        // Filter by tag
        if ($request->has('tag') && $request->input('tag') !== 'All') {
            $tag = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('name', $tag);
            });
        }

        // Sort posts
        $sortBy = $request->input('sort', 'newest');
        if ($sortBy === 'newest') {
            $query->orderBy('created_at', 'desc');
        } elseif ($sortBy === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sortBy === 'popular') {
            $query->orderBy('views', 'desc');
        }

        // Paginate results
        $perPage = $request->input('per_page', 9);
        $blogPosts = $query->paginate($perPage);

        // Transform the data to match the frontend expected format
        $formattedPosts = $blogPosts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'image' => $post->image,
                'author' => $post->author,
                'date' => $post->created_at->toDateString(),
                'likes' => $post->likes,
                'dislikes' => $post->dislikes,
                'views' => $post->views,
                'category' => $post->category,
                'tags' => $post->tags->pluck('name')->toArray(),
                'authorAvatar' => $post->author_avatar,
                'readTime' => $post->read_time,
                'comments' => $post->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'author' => $comment->author,
                        'content' => $comment->content,
                        'date' => $comment->created_at->toDateString(),
                        'likes' => $comment->likes,
                        'authorAvatar' => $comment->author_avatar,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $formattedPosts,
                'pagination' => [
                    'total' => $blogPosts->total(),
                    'per_page' => $blogPosts->perPage(),
                    'current_page' => $blogPosts->currentPage(),
                    'last_page' => $blogPosts->lastPage(),
                ]
            ],
            'message' => 'Blog posts retrieved successfully',
        ]);
    }

    /**
     * Store a newly created blog post.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'excerpt' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author' => 'required|string|max:255',
            'author_avatar' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'read_time' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blog_images', 'public');
            $data['image'] = Storage::url($path);
        }

        // Create blog post
        $blogPost = BlogPost::create($data);

        // Handle tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $blogPost->tags()->sync($tagIds);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatBlogPost($blogPost),
            'message' => 'Blog post created successfully',
        ], 201);
    }

    /**
     * Display the specified blog post.
     */
    public function show($id)
    {
        $blogPost = BlogPost::with(['comments', 'tags'])->findOrFail($id);

        // Increment view count
        $blogPost->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $this->formatBlogPost($blogPost),
            'message' => 'Blog post retrieved successfully',
        ]);
    }

    /**
     * Update the specified blog post.
     */
    public function update(Request $request, $id)
    {
        $blogPost = BlogPost::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'sometimes|required|string',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author' => 'sometimes|required|string|max:255',
            'author_avatar' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'read_time' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($blogPost->image) {
                $oldImagePath = str_replace('/storage/', '', $blogPost->image);
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }

            $path = $request->file('image')->store('blog_images', 'public');
            $data['image'] = Storage::url($path);
        }

        // Update blog post
        $blogPost->update($data);

        // Handle tags
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $blogPost->tags()->sync($tagIds);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatBlogPost($blogPost),
            'message' => 'Blog post updated successfully',
        ]);
    }

    /**
     * Remove the specified blog post.
     */
    public function destroy($id)
    {
        $blogPost = BlogPost::findOrFail($id);

        // Delete image if it exists
        if ($blogPost->image) {
            $imagePath = str_replace('/storage/', '', $blogPost->image);
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $blogPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog post deleted successfully',
        ]);
    }

    /**
     * Get all categories.
     */
    public function getCategories()
    {
        $categories = BlogPost::distinct()->pluck('category')->filter()->values();
        return response()->json([
            'success' => true,
            'data' => $categories,
            'message' => 'Categories retrieved successfully',
        ]);
    }

    /**
     * Get all tags.
     */
    public function getTags()
    {
        $tags = Tag::pluck('name');
        return response()->json([
            'success' => true,
            'data' => $tags,
            'message' => 'Tags retrieved successfully',
        ]);
    }

    /**
     * Format blog post for API response
     */
    private function formatBlogPost(BlogPost $blogPost)
    {
        return [
            'id' => $blogPost->id,
            'title' => $blogPost->title,
            'excerpt' => $blogPost->excerpt,
            'content' => $blogPost->content,
            'image' => $blogPost->image,
            'author' => $blogPost->author,
            'date' => $blogPost->created_at->toDateString(),
            'likes' => $blogPost->likes,
            'dislikes' => $blogPost->dislikes,
            'views' => $blogPost->views,
            'category' => $blogPost->category,
            'tags' => $blogPost->tags->pluck('name')->toArray(),
            'authorAvatar' => $blogPost->author_avatar,
            'readTime' => $blogPost->read_time,
            'comments' => $blogPost->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'author' => $comment->author,
                    'content' => $comment->content,
                    'date' => $comment->created_at->toDateString(),
                    'likes' => $comment->likes,
                    'authorAvatar' => $comment->author_avatar,
                ];
            }),
        ];
    }
}
