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

        $query = Part::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $parts = $query->get()->map(function($part) {
            return $this->formatImageUrls($part);
        });

        return response()->json($parts);
    }

    public function show($id): JsonResponse
    {

        $part = Part::findOrFail($id);
        $part = $this->formatImageUrls($part);

        return response()->json($part);
    }

    public function store(Request $request): JsonResponse
    {

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


        $part = Part::create($validated);
        $part = $this->formatImageUrls($part);

        return response()->json($part, 201);
    }

    public function uploadImage(Request $request): JsonResponse
    {

        try {
            $validated = $request->validate([
                'images' => 'required|array|min:1',
                'images.*' => 'required|image|max:2048', // Removed 'mimes' rule
            ]);


            $files = $request->file('images');
            foreach ($files as $index => $image) {
            }

            $urls = [];
            foreach ($files as $index => $image) {
                $path = $image->store('spares', 'public');
                $url = Storage::url($path);
                $urls[] = ['index' => $index, 'url' => $url, 'path' => $path];
            }

            return response()->json(['urls' => $urls], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to upload images'], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {

        $part = Part::findOrFail($id);

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


        $part->update($validated);
        $part = $this->formatImageUrls($part);

        return response()->json($part);
    }

    public function destroy($id): JsonResponse
    {

        $part = Part::findOrFail($id);

        $part->delete();

        return response()->json(null, 204);
    }
}
