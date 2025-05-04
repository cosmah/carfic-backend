<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
            $category = $request->input('category');
            $query->where('category', $category);
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
                'cover_image' => $post->cover_image ? Storage::url($post->cover_image) : null,
                'author' => $post->author,
                'date' => $post->created_at->toDateString(),
                'likes' => $post->likes,
                'dislikes' => $post->dislikes,
                'views' => $post->views,
                'category' => $post->category,
                'tags' => $post->tags->pluck('name')->toArray(),
                'authorAvatar' => $post->author_avatar,
                'readTime' => $post->read_time,
                'isPublished' => $post->is_published,
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
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author' => 'required|string|max:255',
            'author_avatar' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'read_time' => 'nullable|integer',
            'is_published' => 'boolean',
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
            $randomNumber = random_int(100000000000, 999999999999);
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = "{$randomNumber}_blog.{$extension}";
            $path = $request->file('image')->storeAs('blog_images', $filename, 'public');
            $data['image'] = $path;
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $randomNumber = random_int(100000000000, 999999999999);
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            $filename = "{$randomNumber}_cover.{$extension}";
            $path = $request->file('cover_image')->storeAs('blog_cover', $filename, 'public');
            $data['cover_image'] = $path;
        }

        $blogPost = BlogPost::create($data);

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

        $input = [
            'title' => $request->input('title'),
            'excerpt' => $request->input('excerpt'),
            'content' => $request->input('content'),
            'category' => $request->input('category'),
            'author' => $request->input('author'),
            'is_published' => $request->input('is_published') === 'true' || $request->input('is_published') === true,
        ];

        // Handle tags
        $tags = [];
        $i = 0;
        while ($request->has("tags[{$i}]")) {
            $tags[] = $request->input("tags[{$i}]");
            $i++;
        }
        if (empty($tags) && $request->has('tags')) {
            $tags = $request->input('tags');
            if (!is_array($tags)) {
                $tags = [$tags];
            }
        }
        if (!empty($tags)) {
            $input['tags'] = $tags;
        }

        // Remove null values
        $input = array_filter($input, function ($value) {
            return !is_null($value);
        });

        $validator = Validator::make($input, [
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'sometimes|nullable|string',
            'content' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'author' => 'sometimes|required|string|max:255',
            'author_avatar' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'read_time' => 'nullable|integer',
            'is_published' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid data provided for update',
            ], 400);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            if ($blogPost->image) {
                $oldImagePath = $blogPost->image;
                if (Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }
            }
            $randomNumber = random_int(100000000000, 999999999999);
            $extension = $request->file('image')->getClientOriginalExtension();
            $filename = "{$randomNumber}_blog.{$extension}";
            $path = $request->file('image')->storeAs('blog_images', $filename, 'public');
            $data['image'] = $path;
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            Log::info('Cover image file detected: ', ['file' => $request->file('cover_image')]);
            if ($blogPost->cover_image) {
                $oldCoverPath = $blogPost->cover_image;
                if (Storage::disk('public')->exists($oldCoverPath)) {
                    Storage::disk('public')->delete($oldCoverPath);
                }
            }
            $randomNumber = random_int(100000000000, 999999999999);
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            $filename = "{$randomNumber}_cover.{$extension}";
            $path = $request->file('cover_image')->storeAs('blog_cover', $filename, 'public');
            $data['cover_image'] = $path;
        }

        // Update only if data is present
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

        // Delete the main image if exists
        if ($blogPost->image) {
            $imagePath = $blogPost->image;
            if (Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        // Delete the cover image if exists
        if ($blogPost->cover_image) {
            $coverPath = $blogPost->cover_image;
            if (Storage::disk('public')->exists($coverPath)) {
                Storage::disk('public')->delete($coverPath);
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
            'image' => $blogPost->image ? Storage::url($blogPost->image) : null,
            'cover_image' => $blogPost->cover_image ? Storage::url($blogPost->cover_image) : null,
            'author' => $blogPost->author,
            'date' => $blogPost->created_at->toDateString(),
            'likes' => $blogPost->likes,
            'dislikes' => $blogPost->dislikes,
            'views' => $blogPost->views,
            'category' => $blogPost->category,
            'tags' => $blogPost->tags->pluck('name')->toArray(),
            'authorAvatar' => $blogPost->author_avatar,
            'readTime' => $blogPost->read_time,
            'isPublished' => $blogPost->is_published,
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
