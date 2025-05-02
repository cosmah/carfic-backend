<?php
namespace App\Http\Controllers;

use App\Models\Part;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PartController extends Controller
{
    private function formatImageUrls($part)
    {
        if ($part->image) {
            $part->image = str_starts_with($part->image, 'http')
                ? $part->image
                : Storage::url($part->image);
        }

        if ($part->images && is_array($part->images)) {
            $part->images = array_map(function($image) {
                if (isset($image['src'])) {
                    $image['src'] = str_starts_with($image['src'], 'http')
                        ? $image['src']
                        : Storage::url($image['src']);
                }
                return $image;
            }, $part->images);
        }

        return $part;
    }

    public function index(Request $request): JsonResponse
    {
        Log::info('Index method called', ['query' => $request->query()]);

        $query = Part::query();

        if ($search = $request->query('search')) {
            Log::info('Search filter applied', ['search' => $search]);
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->query('category')) {
            Log::info('Category filter applied', ['category' => $category]);
            $query->where('category', $category);
        }

        $parts = $query->get()->map(function($part) {
            return $this->formatImageUrls($part);
        });

        Log::info('Parts retrieved', ['count' => $parts->count()]);
        return response()->json($parts);
    }

    public function show($id): JsonResponse
    {
        Log::info('Show method called', ['id' => $id]);

        $part = Part::findOrFail($id);
        $part = $this->formatImageUrls($part);

        Log::info('Part retrieved', ['part_id' => $part->id]);
        return response()->json($part);
    }

    public function store(Request $request): JsonResponse
    {
        Log::info('Store method called', ['request' => $request->all()]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'description' => 'required|string',
            'features' => 'nullable|array',
            'specifications' => 'nullable|array',
            'specifications.*.name' => 'required_with:specifications|string',
            'specifications.*.value' => 'required_with:specifications|string',
            'images' => 'nullable|array',
            'images.*.src' => 'required_with:images|string',
            'images.*.alt' => 'nullable|string',
            'compatibility' => 'nullable|array',
            'compatibility.*.make' => 'required_with:compatibility|string',
            'compatibility.*.models' => 'required_with:compatibility|array',
            'image' => 'nullable|string',
            'status' => 'required|in:draft,published',
        ]);

        Log::info('Validation passed', ['validated' => $validated]);

        $part = Part::create($validated);
        $part = $this->formatImageUrls($part);

        Log::info('Part created', ['part_id' => $part->id]);
        return response()->json($part, 201);
    }

    public function uploadImage(Request $request): JsonResponse
    {
        Log::info('UploadImage method called', ['request' => $request->all()]);

        try {
            $validated = $request->validate([
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|max:2048', // Removed 'mimes' rule
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            $files = $request->file('images');
            foreach ($files as $index => $image) {
                Log::info('File MIME type', ['index' => $index, 'mime_type' => $image->getMimeType()]);
            }

            $urls = [];
            foreach ($files as $index => $image) {
                $path = $image->store('spares', 'public');
                $url = Storage::url($path);
                $urls[] = ['index' => $index, 'url' => $url, 'path' => $path];
                Log::info('Image stored', ['index' => $index, 'path' => $path, 'url' => $url]);
            }

            return response()->json(['urls' => $urls], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Image upload failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to upload images'], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        Log::info('Update method called', ['id' => $id, 'request' => $request->all()]);

        $part = Part::findOrFail($id);
        Log::info('Part found', ['part_id' => $part->id]);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            'description' => 'sometimes|string',
            'features' => 'nullable|array',
            'specifications' => 'nullable|array',
            'specifications.*.name' => 'required_with:specifications|string',
            'specifications.*.value' => 'required_with:specifications|string',
            'images' => 'nullable|array',
            'images.*.src' => 'required_with:images|string',
            'images.*.alt' => 'nullable|string',
            'compatibility' => 'nullable|array',
            'compatibility.*.make' => 'required_with:compatibility|string',
            'compatibility.*.models' => 'required_with:compatibility|array',
            'image' => 'nullable|string',
            'status' => 'sometimes|in:draft,published',
        ]);

        Log::info('Validation passed', ['validated' => $validated]);

        $part->update($validated);
        $part = $this->formatImageUrls($part);

        Log::info('Part updated', ['part_id' => $part->id]);
        return response()->json($part);
    }

    public function destroy($id): JsonResponse
    {
        Log::info('Destroy method called', ['id' => $id]);

        $part = Part::findOrFail($id);
        Log::info('Part found', ['part_id' => $part->id]);

        $part->delete();
        Log::info('Part deleted', ['id' => $id]);

        return response()->json(null, 204);
    }
}
